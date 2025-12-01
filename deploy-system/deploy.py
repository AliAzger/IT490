#!/usr/bin/python

import json
import os
import pika

# constants/config
RABBITMQ_IP = "100.93.74.30" # my testing ip
# RABBITMQ_IP = "172.25.28.168"

# globals
PACKAGE_INFO = None
RABBITMQ_CHANNEL = None

def init_rabbit_connection():
  global RABBITMQ_CHANNEL
  creds = pika.PlainCredentials('test', 'test')
  connection = pika.BlockingConnection(pika.ConnectionParameters(RABBITMQ_IP, 5672, 'testHost', creds))
  channel = connection.channel()
  channel.queue_declare(queue='deployment')
  RABBITMQ_CHANNEL = channel

def send_rabbit_message(message:str, wait_for_res=True):
  RABBITMQ_CHANNEL.queue_declare(queue='deployment_response')
  properties = pika.BasicProperties(reply_to='deployment_response')
  RABBITMQ_CHANNEL.basic_publish(exchange='', routing_key='deployment', body=message, properties=properties)
  
  if wait_for_res:
    for method, prop, body in RABBITMQ_CHANNEL.consume("deployment_response", True):
      RABBITMQ_CHANNEL.cancel()
      return json.loads(body)

def load_package_config(path):
  with open(path) as file:
    package_info = json.load(file)
    return package_info

def choose_package():
  selection = None
  
  while not selection:
    print("Choose the number of the package to deploy:")
    i = 1
    choices = [package_name for package_name in PACKAGE_INFO["packages"]]
    for option in choices:
      print(f"{i}.", option)
      i = i + 1
    
    try: choice = int(input("Enter number: "))
    except: continue
    if choice > len(choices): continue
    
    selection = choices[choice-1]
  
  return selection

def get_current_package_version(package_name):
  print("requesting", package_name, "version")
  message_body = {
    "event": "package_version",
    "package": package_name
  }
  message_body_str = json.dumps(message_body)
  
  res = send_rabbit_message(message_body_str)
  
  if not res["success"]:
    print(f"Deployment server error getting version for package {package_name}")
    quit()
  
  print("  found version", res["version"])
  return res["version"]

def main():
  global PACKAGE_INFO
  
  if "deploy-system" not in os.getcwd():
    print("please run this script from the deploy-system directory")
    quit()
  
  init_rabbit_connection()
  
  PACKAGE_INFO = load_package_config("package_config.json")
      
  chosen_package = choose_package()
  version = get_current_package_version(chosen_package)
  
  selected_version = int(input(f"version to deploy (most recent is {version}): "))
  selected_env = input(f"environment to deploy to (qa/prod): ")
  if selected_env.lower() not in ["qa", "prod"]:
    print("bad env")
    quit()
  
  message_body = {
    "event": "deploy",
    "package": chosen_package,
    "version": selected_version,
    "env": selected_env
  }
  message_body_str = json.dumps(message_body)
  
  send_rabbit_message(message_body_str, wait_for_res=False)

if __name__ == "__main__":
  main()