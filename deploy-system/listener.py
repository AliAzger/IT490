#!/usr/bin/python

import json
import pika
import time
import mysql.connector
import os
import requests
import subprocess

# constants/config
RABBITMQ_IP = "100.93.74.30" # my testing ip
# RABBITMQ_IP = "172.25.28.168"

DATABASE_IP = "127.0.0.1" # my testing ip
# DATABASE_IP = "172.25.199.66"

PUBLISHER_IP = "100.97.33.42" # my testing ip

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
        host=DATABASE_IP,
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

def trigger_deployment(package, archive, env:str):
  if env.lower() not in ["qa", "prod"]:
    print("invalid environment; must be qa or prod")
    return False
  return True if subprocess.run(["./deploy.sh", package, archive, env.lower()]).returncode == 0 else False

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
        
        query = f"SELECT * FROM deployment WHERE package_name = '{package}' ORDER BY version DESC"
        cursor = MYSQL_DATABASE.cursor()
        cursor.execute(query)
        result = cursor.fetchall()
        
        print(result)
        
        if len(result) <= 0:
          response["version"] = 0
        else:
          response["version"] = result[0][2] # 2 means version
        
        response["success"] = 1
      case "publish_package":
        package = message["package"]
        version = message["version"]
        archive = message["archive"]
        
        query = f"INSERT INTO deployment (package_name, version, archive) VALUES ('{package}', {version}, '{archive}')"
        cursor.execute(query)
        MYSQL_DATABASE.commit()
        
        time.sleep(2) # wait a bit for http server to open up
        
        # download archive
        res = requests.get(f"http://{PUBLISHER_IP}:8000/{archive}")
        res.raise_for_status()
        
        with open(archive, "wb") as f:
          f.write(res.content)

        response["success"] = 1
        
        # try autodeplying to qa
        trigger_deployment(package, archive, "qa")
      case "deploy":
        package = message["package"]
        version = message["version"]
        env = message["env"]
        archive = None
        
        # get version's archive
        query = f"SELECT archive FROM deployment WHERE package_name = '{package}' AND version = {version}"
        cursor = MYSQL_DATABASE.cursor()
        cursor.execute(query)
        result = cursor.fetchall()
        
        print(result)
        
        if len(result) <= 0:
          response["success"] = 0
          response["comment"] = "version does not exist"
          print("Invalid version to deploy")
        elif len(result) == 1:
          archive = result[0][0]
        
        if archive:
          # TODO do deployment (call bash script, or wahtever)
          deployed = trigger_deployment(package, archive, env)
          response["success"] = int(deployed)
      case _:
        response["success"] = 0
        response["comment"] = "unknown event"
    
    print("  responding", response)
    send_rabbit_response(response)

def main():
  if "deploy-system" not in os.getcwd():
    print("please run this script from the deploy-system directory")
    quit()
  
  init_rabbit_connection()
  init_mysql_connection()
  listen_for_rabbit_messages()

if __name__ == "__main__":
  main()