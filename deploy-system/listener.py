#!/usr/bin/python

import json
import pika
import time
import mysql.connector

# constants/config
RABBITMQ_IP = "100.93.74.30" # my testing ip
# RABBITMQ_IP = "172.25.28.168"

DATABASE_IP = "127.0.0.1" # my testing ip
# DATABASE_IP = "172.25.199.66"

# globals
RABBITMQ_CHANNEL = None
MYSQL_DATABASE = None

def init_rabbit_connection():
  global RABBITMQ_CHANNEL
  print("trying to connect to rabbit...")
  creds = pika.PlainCredentials('test', 'test')
  connection = pika.BlockingConnection(pika.ConnectionParameters(RABBITMQ_IP, 5672, 'testHost', creds))
  channel = connection.channel()
  channel.queue_declare(queue='deployment')
  channel.queue_declare(queue='deployment_response')
  RABBITMQ_CHANNEL = channel
  print("  connected")

def init_mysql_connection():
  global MYSQL_DATABASE
  print("trying to connect to db...")
  try:
    mydb = mysql.connector.connect(
        host="localhost",
        user="testUser",
        password="12345",
        database="testdb"
    )
    MYSQL_DATABASE = mydb
    print("  connected")
  except mysql.connector.Error as err:
    print(f"Error connecting to MySQL: {err}")

def send_rabbit_response(messageObj):
  message = json.dumps(messageObj)
  RABBITMQ_CHANNEL.basic_publish(exchange='', routing_key='deployment_response', body=message)

def listen_for_rabbit_messages():
  for method, prop, body in RABBITMQ_CHANNEL.consume("deployment", True):
    message = json.loads(body)
    print("received", message)
    response = {}
    
    if not message.get("event"):
      response["success"] = 0
      response["comment"] = "no event property"
      send_rabbit_response(response)
      continue
        
    match message["event"]:
      case "package_version":
        package = message["package"]
        
        # TODO: check package version in database
        query = f"SELECT * FROM deployment WHERE package_name = '{package}' ORDER BY version DESC"
        cursor = MYSQL_DATABASE.cursor()
        cursor.execute(query)
        result = cursor.fetchall()
        
        for entry in result:
          print(entry)
        
        response["success"] = 1
        response["version"] = result[0]['version']
      case "publish_package":
        package = message["package"]
        version = message["version"]
        archive = message["archive"]
        
        # TODO update database with new package info
        query = f"INSERT INTO deployment (package_name, version, archive) VALUES ('{package}', {version}, '{archive}')"
        cursor.execute(query)
        MYSQL_DATABASE.commit()

        response["success"] = 1
      case "deploy":
        package = message["package"]
        version = message["version"]
        env = message["env"]
        
        time.sleep(3) # wait a bit for http server to open up
        
        # TODO do deployment (call bash script, or wahtever)
        response["success"] = 1
      case _:
        response["success"] = 0
        response["comment"] = "unknown event"
    
    print("  responding", response)
    send_rabbit_response(response)

def main():
  init_rabbit_connection()
  listen_for_rabbit_messages()

if __name__ == "__main__":
  main()