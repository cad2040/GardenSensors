#include <ESP8266WiFi.h>
#include <MySQL_Connection.h>
#include <MySQL_Cursor.h>
#include <WiFiClient.h>
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

// Configuration structure
struct Config {
  const char* ssid;
  const char* password;
  IPAddress server_addr;
  char mysql_user[32];
  char mysql_pass[32];
  const char* host;
  unsigned int reading_delay_ms;
  unsigned int reading_count;
  unsigned int moisture_min;
  unsigned int moisture_max;
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
  732   // moisture_max (wet soil)
};

void setup() {
  // start serial communication at 115200 bits per second:
  Serial.begin(115200);
  delay(10);

  // connect to WiFi
  WiFi.begin(config.ssid, config.password);
  
  // Connect to WiFi with timeout
  unsigned long startAttemptTime = millis();
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    
    // Timeout after 30 seconds
    if (millis() - startAttemptTime > 30000) {
      Serial.println("\nFailed to connect to WiFi. Restarting...");
      ESP.restart();
    }
  }
  Serial.println("\nWiFi connected");
  delay(2000);

  // open connection to MySQL DB
  unsigned long dbStartAttemptTime = millis();
  while (conn.connect(config.server_addr, 3306, config.mysql_user, config.mysql_pass) != true) {
    delay(500);
    Serial.print ( "." );
    
    // Timeout after 30 seconds
    if (millis() - dbStartAttemptTime > 30000) {
      Serial.println("\nFailed to connect to MySQL. Restarting...");
      ESP.restart();
    }
  }
  Serial.println("\nMySQL connected");
}

void loop() {
  // Local variables
  values_avg = 0;
  moisture = 0;
  counter = 0;
  
  // Take multiple readings for averaging
  for(counter = 0; counter < config.reading_count; counter++) {
    analogVals[counter] = analogRead(A0);
    delay(config.reading_delay_ms);
    values_avg += analogVals[counter];
  }

  // Calculate mean average reading
  values_avg = values_avg / config.reading_count;
  
  // Map soil conductivity reading between 0-100%
  // Note: 732 is typical reading for wet soil, 270 for dry soil
  moisture = map(values_avg, config.moisture_min, config.moisture_max, 0, 100);
  
  // Create cursor to execute SQL query
  MySQL_Cursor *cur_mem = new MySQL_Cursor(&conn);
  
  // Parse and execute query with error handling
  sprintf(query, INSERT_SQL_FORMAT, moisture);
  if (!cur_mem->execute(query)) {
    Serial.println("Failed to execute query");
  }
  
  delete cur_mem;

  // Run again in 1 hour
  delay(60*60*1000UL);
}
