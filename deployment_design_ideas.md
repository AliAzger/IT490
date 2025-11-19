# Design ideas for deployment system:
We need to figure out a deployment system for the project. Here,I'll list requirements, ideas to fit those requirements, and any potential issues we may have with them.

## Requirements:
- new vm; deployment server
- discrete packages of code
    - package versioning
    - status tracking (mark package as failed/passed)
- new db in this vm (for tracking packages)
- auto install to environments
- rollback

## Idea 1: Python + Bash!
Python to publish new package versions to deployment server, Bash to deploy to VMs
- Python is easy, portable, and quick to prototype/debug
- Can run in a venv for consistency/repeatability
- Bash is easy for system utils/file operations

### Using Python to publish:
- Config file (JSON, YAML, whatever) that maps `package_name`s to a list of files that belong in package
    - also describes which files go to which vms
- run python script (from dev machine, maybe backend?)
- user picks which package to publish
- get current version for that package from deployment server
- increment version for package
- create archive for `package_name-version` containing all included files
    - files organized by VM they belong in
- allow that archive to be downloaded via scp
- send name+version through rabbit to deployment server

### Using Bash to deploy:
- php listener to
    - report current package versions when requested
    - receive new package versions
    - add new package version numbers to database
    - trigger Bash script to run when request to deploy is received
- Bash code will
    - take in environment to deploy to
    - scp the archive from the publishing machine to the deployment server
    - unarchive
    - scp files from each VM dir to the proper VM in the specified env
        - NOTE: may need to find a way to restart services if necessary after files are replaced