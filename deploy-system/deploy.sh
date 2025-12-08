#!/bin/bash

QA_IP="100.117.191.39" # testing ip
PROD_IP="prod"

if [ "$#" -ne 3 ]; then
    echo "Usage: $0 <package_name> <archive_name> <environment: qa | prod>"
    exit 1
fi

# take in archive name and environment
package_name=$1
archive_name=$2
env=$3

if [ "$env" != 'qa' ] && [ "$env" != 'prod' ]; then
    echo "Environment can only be either 'qa' or 'prod'"
    exit 1
fi

# set IP of env to deploy to
if [ "$env" == 'qa' ]; then
    dest_ip=$QA_IP
fi

if [ "$env" == 'prod' ]; then
    dest_ip=$PROD_IP
fi

# ensure env is up
ping -w 3 -c 1 $dest_ip

while [ $? -ne 0 ]; do
    sleep 5
    ping -w 3 -c 1 $dest_ip
done

ls packages | grep "$archive_name"

if [ $? -ne 0 ]; then
    echo "Cannot find archive ($archive_name)"
    exit 1
fi

# unarchive the package
tar -xzf "packages/$archive_name"

if [ $? -ne 0 ]; then
    echo "Failed to unarchive"
    exit 1
fi

# scp files to the VMs

# copy to webserver
ls $package_name | grep "webserver"
if [ $? -eq 0 ]; then
    scp -r $package_name/webserver/* $dest_ip:/var/www/sample/
    if [ $? -ne 0 ]; then
        echo "Failed to deploy webserver files to $dest_ip"
    fi
fi

# copy to dmz
ls $package_name | grep "dmz"
if [ $? -eq 0 ]; then
    scp -r $package_name/dmz/* $dest_ip: # NEED TO PUT PATH HERE FOR DMZ FILES TO GO TO
    if [ $? -ne 0 ]; then
        echo "Failed to deploy dmz files to $dest_ip"
    fi
fi

rm -r $package_name

# restart services?
# would need a new user with permissions to do this; trying to avoid making deploy user
# ssh $dest_ip "systemctl restart apache2.service"