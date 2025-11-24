#!/usr/bin/python

import json
import os
import shutil
from http import server

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

def send_rabbit_message(message:str):
  RABBITMQ_CHANNEL.queue_declare(queue='deployment_response')
  properties = pika.BasicProperties(reply_to='deployment_response')
  RABBITMQ_CHANNEL.basic_publish(exchange='', routing_key='deployment', body=message, properties=properties)
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
    print("Choose the number of the package to publish:")
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
  message_body = {
    "event": "package_version",
    "package": package_name
  }
  message_body_str = json.dumps(message_body)
  
  res = send_rabbit_message(message_body_str)
  
  if not res["success"]:
    print(f"Deployment server error getting version for package {package_name}")
    quit()
    
  return res["version"]

def publish_package(package_name, version, archive_path):
  print("publishing", package_name, "version", version)

  message_body = {
    "event": "publish_package",
    "package": package_name,
    "version": version,
    "archive": archive_path,
  }
  message_body_str = json.dumps(message_body)
  
  res = send_rabbit_message(message_body_str)
  
  if not res["success"]:
    print("Deployment server error publishing package")
    quit()

def create_package_archive(package_name, version):
  archive_name = None
  os.mkdir(package_name)
  
  for vm, files in PACKAGE_INFO["packages"][package_name]["files"].items():
    os.mkdir(f"{package_name}/{vm}")
    for file in files:
      shutil.copyfile(f"../{file}", f"{package_name}/{vm}/{file}")
  
  archive_name = shutil.make_archive(f"{package_name}-{version}", "gztar", "..")
    
  shutil.rmtree(package_name)
  return archive_name

def main():
  global PACKAGE_INFO
  
  init_rabbit_connection()
  
  PACKAGE_INFO = load_package_config("package_config.json")
      
  chosen_package = choose_package()
  version = get_current_package_version(chosen_package) + 1  
  archive = create_package_archive(chosen_package, version)
  
  publish_package(chosen_package, version, archive)
  
  # TODO allow server to grab archive (http server? scp/ftp?)

if __name__ == "__main__":
  main()