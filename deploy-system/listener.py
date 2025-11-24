#!/usr/bin/python

import json
import pika
import time

# constants/config
# RABBITMQ_IP = "100.93.74.30" # my testing ip
RABBITMQ_IP = "172.25.28.168"

# globals
RABBITMQ_CHANNEL = None

def init_rabbit_connection():
  global RABBITMQ_CHANNEL
  print("trying to connect to rabbit...")
  creds = pika.PlainCredentials('test', 'test')
  connection = pika.BlockingConnection(pika.ConnectionParameters(RABBITMQ_IP, 5672, 'testHost', creds))
  channel = connection.channel()
  channel.queue_declare(queue='deployment')
  channel.queue_declare(queue='deployment_response')
  RABBITMQ_CHANNEL = channel
  print("connected")

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
        response["success"] = 1
        response["version"] = 0
      case "publish_package":
        package = message["package"]
        version = message["version"]
        archive = message["archive"]
        
        # TODO update database with new package info
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