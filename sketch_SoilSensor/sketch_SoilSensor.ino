#include <ESP8266WiFi.h>
#include <MySQL_Connection.h>
#include <MySQL_Cursor.h>
#include <WiFiClient.h>
#include <EEPROM.h>
#include <ESP8266WebServer.h>
#include <ESP8266mDNS.h>

// EEPROM configuration
#define EEPROM_SIZE 512
#define EEPROM_START_ADDR 0

// LED pin for status indication
const int LED_PIN = 2;  // Built-in LED on most ESP8266 boards

// Watchdog timeout (8 seconds)
const unsigned long WATCHDOG_TIMEOUT = 8000000;

// Web server authentication
const char* www_username = "admin";
const char* www_password = "admin";

//Connect to Wifi Network
const char* ssid     = "Enter Network Name";
const char* password = "Enter Network Password";
WiFiClient client;
// MySQL server details
IPAddress server_addr(192,168,1,xx); // IP of the MySQL server here
char user[] = "Enter MySQL Uname"; // MySQL user login username
char pass[] = "Enter MySQL Password"; // MySQL user login password

//Query to insert readings into MySQL, update value 1 to sensor id
char INSERT_SQL_FORMAT[] = "INSERT INTO SoilSensors.Readings (sensor_id, reading) VALUES (1, %f)";
char query[128];
MySQL_Connection conn((Client *)&client);
 
// global variables
const char host[] = "Sector01Sensor"; // update for each sensor
float moisture = 0;
const unsigned reading_count = 10; // Take 10 readings to get an average
unsigned int analogVals[reading_count];
unsigned int counter = 0;
unsigned int values_avg = 0;
unsigned long last_reading_time = 0;
ESP8266WebServer webServer(80);
bool isConfigMode = false;

// Configuration structure
struct Config {
  char ssid[32];
  char password[64];
  IPAddress server_addr;
  char mysql_user[32];
  char mysql_pass[32];
  char host[32];
  unsigned int reading_delay_ms;
  unsigned int reading_count;
  unsigned int moisture_min;
  unsigned int moisture_max;
  char insert_query_format[128];
  unsigned long sleep_duration_ms;
  unsigned long connection_timeout_ms;
  float min_valid_moisture;
  float max_valid_moisture;
  unsigned long min_reading_interval_ms;
};

// Default configuration
Config config = {
  "Enter Network Name",
  "Enter Network Password",
  IPAddress(192,168,1,xx),
  "Enter MySQL Uname",
  "Enter MySQL Password",
  "Sector01Sensor",
  100,  // reading_delay_ms
  10,   // reading_count
  270,  // moisture_min (dry soil)
  732,  // moisture_max (wet soil)
  "INSERT INTO SoilSensors.Readings (sensor_id, reading) VALUES (1, %f)",
  60*60*1000UL,  // sleep_duration_ms (1 hour)
  30000,         // connection_timeout_ms (30 seconds)
  0.0,           // min_valid_moisture
  100.0,         // max_valid_moisture
  5*60*1000UL    // min_reading_interval_ms (5 minutes)
};

// Error handling function with LED indication
void handleError(const char* error_msg) {
  Serial.println(error_msg);
  // Blink LED rapidly to indicate error
  for(int i = 0; i < 5; i++) {
    digitalWrite(LED_PIN, LOW);
    delay(100);
    digitalWrite(LED_PIN, HIGH);
    delay(100);
  }
}

// LED status indicators with more states
void indicateStatus(const char* status) {
  if (strcmp(status, "connecting") == 0) {
    digitalWrite(LED_PIN, LOW);  // LED on
  } else if (strcmp(status, "reading") == 0) {
    digitalWrite(LED_PIN, HIGH);  // LED off
  } else if (strcmp(status, "error") == 0) {
    handleError(status);
  } else if (strcmp(status, "config") == 0) {
    // Blink slowly for config mode
    digitalWrite(LED_PIN, LOW);
    delay(500);
    digitalWrite(LED_PIN, HIGH);
    delay(500);
  }
}

// Validate moisture reading
bool isValidReading(float reading) {
  return (reading >= config.min_valid_moisture && 
          reading <= config.max_valid_moisture);
}

// Check if enough time has passed since last reading
bool canTakeReading() {
  return (millis() - last_reading_time >= config.min_reading_interval_ms);
}

// Web server handlers
void handleRoot() {
  if (!webServer.authenticate(www_username, www_password)) {
    return webServer.requestAuthentication();
  }
  
  String html = "<html><head><meta name='viewport' content='width=device-width, initial-scale=1'>";
  html += "<style>body{font-family:Arial;margin:20px;}</style></head><body>";
  html += "<h1>Soil Sensor Status</h1>";
  html += "<p>Last Reading: " + String(moisture, 2) + "%</p>";
  html += "<p>Last Reading Time: " + String(last_reading_time) + "</p>";
  html += "<p>WiFi Signal Strength: " + String(WiFi.RSSI()) + " dBm</p>";
  html += "<p>Device Uptime: " + String(millis() / 1000) + " seconds</p>";
  html += "<p>Wake-up Reason: " + String(ESP.getResetReason()) + "</p>";
  html += "<p><a href='/config'>Configure Device</a></p>";
  html += "</body></html>";
  webServer.send(200, "text/html", html);
}

void handleConfig() {
  if (!webServer.authenticate(www_username, www_password)) {
    return webServer.requestAuthentication();
  }
  
  if (webServer.hasArg("ssid")) {
    strncpy(config.ssid, webServer.arg("ssid").c_str(), sizeof(config.ssid));
    strncpy(config.password, webServer.arg("password").c_str(), sizeof(config.password));
    saveConfig();
    webServer.send(200, "text/plain", "Configuration saved. Device will restart...");
    delay(1000);
    ESP.restart();
  }
  
  String html = "<html><head><meta name='viewport' content='width=device-width, initial-scale=1'>";
  html += "<style>body{font-family:Arial;margin:20px;}input{width:100%;margin:5px 0;}</style></head><body>";
  html += "<h1>Device Configuration</h1>";
  html += "<form method='post'>";
  html += "<p>WiFi SSID:<br><input type='text' name='ssid' value='" + String(config.ssid) + "'></p>";
  html += "<p>WiFi Password:<br><input type='password' name='password' value='" + String(config.password) + "'></p>";
  html += "<p><input type='submit' value='Save Configuration'></p>";
  html += "</form>";
  html += "<p><a href='/'>Back to Status</a></p>";
  html += "</body></html>";
  webServer.send(200, "text/html", html);
}

// Reconnect to WiFi with retry
bool connectWiFi() {
  indicateStatus("connecting");
  WiFi.begin(config.ssid, config.password);
  
  unsigned long startAttemptTime = millis();
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    
    if (millis() - startAttemptTime > config.connection_timeout_ms) {
      handleError("WiFi connection failed");
      return false;
    }
  }
  Serial.println("\nWiFi connected");
  return true;
}

// Reconnect to MySQL with retry
bool connectMySQL() {
  unsigned long dbStartAttemptTime = millis();
  while (conn.connect(config.server_addr, 3306, config.mysql_user, config.mysql_pass) != true) {
    delay(500);
    Serial.print(".");
    
    if (millis() - dbStartAttemptTime > config.connection_timeout_ms) {
      handleError("MySQL connection failed");
      return false;
    }
  }
  Serial.println("\nMySQL connected");
  return true;
}

// EEPROM functions
void saveConfig() {
  EEPROM.put(EEPROM_START_ADDR, config);
  EEPROM.commit();
}

void loadConfig() {
  EEPROM.get(EEPROM_START_ADDR, config);
}

void loop() {
  // Reset watchdog timer
  ESP.wdtReset();
  
  // Handle web server requests
  webServer.handleClient();
  
  // If in config mode, don't proceed with readings
  if (isConfigMode) {
    indicateStatus("config");
    return;
  }
  
  // Check if we can take a new reading
  if (!canTakeReading()) {
    delay(1000);  // Wait 1 second before checking again
    return;
  }
  
  // Local variables
  values_avg = 0;
  moisture = 0;
  
  // Take multiple readings for averaging
  indicateStatus("reading");
  for(int counter = 0; counter < config.reading_count; counter++) {
    analogVals[counter] = analogRead(A0);
    delay(config.reading_delay_ms);
    values_avg += analogVals[counter];
  }
  
  // Calculate mean average reading
  values_avg = values_avg / config.reading_count;
  
  // Map soil conductivity reading between 0-100%
  moisture = map(values_avg, config.moisture_min, config.moisture_max, 0, 100);
  
  // Validate reading
  if (!isValidReading(moisture)) {
    handleError("Invalid moisture reading detected");
    return;
  }
  
  // Update last reading time
  last_reading_time = millis();
  
  // Create cursor to execute SQL query
  MySQL_Cursor *cur_mem = new MySQL_Cursor(&conn);
  
  // Parse and execute query with enhanced error handling
  sprintf(query, config.insert_query_format, moisture);
  if (!cur_mem->execute(query)) {
    handleError("Failed to execute MySQL query");
  } else {
    Serial.printf("Successfully recorded moisture reading: %.2f%%\n", moisture);
  }
  
  delete cur_mem;
  
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    if (!connectWiFi()) {
      ESP.restart();
    }
  }
  
  // Check MySQL connection
  if (!conn.connected()) {
    if (!connectMySQL()) {
      ESP.restart();
    }
  }
  
  // Stop web server before deep sleep
  webServer.stop();
  
  // Enter deep sleep
  Serial.println("Entering deep sleep...");
  ESP.deepSleep(config.sleep_duration_ms * 1000);  // Convert to microseconds
}

// Add this function to handle wake-up
void setup() {
  Serial.begin(115200);
  delay(10);
  
  // Initialize EEPROM
  EEPROM.begin(EEPROM_SIZE);
  loadConfig();
  
  // Initialize LED
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, HIGH);  // LED off initially
  
  // Initialize web server
  webServer.on("/", handleRoot);
  webServer.on("/config", handleConfig);
  webServer.begin();
  
  // Initialize mDNS
  if (MDNS.begin(config.host)) {
    Serial.println("mDNS responder started");
    Serial.print("You can now connect to http://");
    Serial.print(config.host);
    Serial.println(".local");
  }
  
  // Connect to WiFi
  if (!connectWiFi()) {
    indicateStatus("config");
    isConfigMode = true;
    return;  // Stay in config mode
  }
  
  // Connect to MySQL
  if (!connectMySQL()) {
    handleError("MySQL connection failed");
    ESP.restart();
  }
  
  // Initialize watchdog
  ESP.wdtEnable(WATCHDOG_TIMEOUT);
  
  // Print wake-up reason
  Serial.println("Wake-up reason: " + String(ESP.getResetReason()));
}
