

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

  // connect to WiFi
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  
}
  delay(2000);

  // open connection to MySQL DB
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
    analogVals[reading_count] = analogRead(A0);
    delay(100);

    // Add reading to running total
    values_avg = (values_avg + analogVals[reading_count]);
  }

  // Calculate mean average reading
  values_avg = values_avg/reading_count;
  // invert and map soil conductivity reading between 0 - 100.
  moisture = map(values_avg,732,270,0,100);
  
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
