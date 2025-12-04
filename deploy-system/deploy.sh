#!/bin/bash

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
    dest_ip="100.117.191.39" # testing ip
fi

if [ "$env" == 'prod' ]; then
    dest_ip="prod"
fi

ls | grep "$archive_name"

if [ $? -ne 0 ]; then
    echo "Cannot find archive ($archive_name)"
    exit 1
fi

# unarchive the package
tar -xzf "$archive_name"

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

rm -r $package_name

# restart services?
# ssh $dest_ip "systemctl restart apache2.service"