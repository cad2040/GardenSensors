

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

void setup() {
  // start serial communication at 115200 bits per second:
  Serial.begin(115200);
  delay(10);
 Serial.println();
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);

  // connect to WiFi
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  
}
  Serial.println("");
  Serial.println("WiFi connected");  
  Serial.println("IP address: ");
  Serial.println(WiFi.localIP());
  delay(2000);

  // open connection to MySQL DB
  Serial.println("DB - Connecting...");
  
  while (conn.connect(server_addr, 3306, user, pass) != true) {
    delay(500);
    Serial.print ( "." );
}
}

void loop() {
  // Local variables
  values_avg = 0;
  moisture = 0;
  counter = 0;
  
  // read the input on analog pin 0:
  for( counter = 0; counter < reading_count; counter++){
    Serial.println("Reading sensor value...:");
    analogVals[reading_count] = analogRead(A0);
    delay(100);

    // Add reading to total value
    values_avg = (values_avg + analogVals[reading_count]);
    Serial.println(analogVals[reading_count]);
    Serial.print("Total Readings value...:");
    Serial.println(values_avg);
  }

  // Calculate mean reading
  values_avg = values_avg/reading_count;
  Serial.print("Average Readings value...:");
  Serial.println(values_avg);
  // scale moisture value if moisture > 80 = wet, <40 = dry, goldielocks >40 && <80
  moisture = map(values_avg,0,400,0,100);
  // print out the sensor reading:
  Serial.print("Average moisture value...:");
  Serial.println(moisture);
  
  // Create cursor to execute SQL query
  MySQL_Cursor *cur_mem = new MySQL_Cursor(&conn);
  // Parse query to insert into SQL Table
  sprintf(query, INSERT_SQL_FORMAT, moisture);
  // Execute the query
  cur_mem->execute(query);
  // Delete the cursor
  delete cur_mem;

  // run again in 1 hour
  delay(60*60*1000UL);
  
}
