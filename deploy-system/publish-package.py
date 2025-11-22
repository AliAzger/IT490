#!/usr/bin/python

import json
import os
import shutil
from http import server

PACKAGE_INFO = None

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
  res = 0 # TODO get this from server thru rabbitmq
  
  if res == None:
    print("Deployment server error")
    quit()
    
  return res

def publish_package(package_name, version, archive_name):
  print("publishing", package_name, "version", version)
  # TODO send package name, version, and archive path to server thru rabbit

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
  PACKAGE_INFO = load_package_config("package_config.json")
      
  chosen_package = choose_package()
  version = get_current_package_version(chosen_package) + 1  
  archive = create_package_archive(chosen_package, version)
  
  publish_package(chosen_package, version, archive)
  
  # TODO allow server to grab archive (http server? scp/ftp?)

if __name__ == "__main__":
  main()