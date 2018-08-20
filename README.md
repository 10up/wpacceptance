assurewp run

assurewp run --local

assurewp run --snapshot-id

assurewp configure

-----------------------------------------------



assurewp run --db_host=localhost
  - copy assurewp.json to /wp-content/.assurewp/
  - wpsnapshots create
       Saves sql and files at /wp/.wpsnapshots/SNAPID/* (does not push remotely)
  - spin up php, mysql and nginx container
  - within container, wpsnapshots pull SNAPID
  - Setup WP core, generate wp-config.php
  - Finish and output test package snapshot ID
  --lock parameter saves snapshot ID to assurewp.json
  - This test package snapshot has been saved locally? Do you want to push it to the remote repository? `wpsnapshots push --snapshot-id=<snapshot-id>`


assurewp run --snapshot-id=356etg3r45erw
  - spin up php, mysql and nginx container
  - within container, wpsnapshots pull SNAPID
  - run tests at /wp-content







Remote Workflow:

- wpsnapshots push (send to S3)
- (inside server) wpsnapshots pull
- assurewp --db_host=localhost


Example assurewp.json:

{
  "snapshot-id": "8sdoh2tsld223ttsd",
  "tests": "tests/js/*"
}
