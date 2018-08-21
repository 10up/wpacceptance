wpassure run

wpassure run --local

wpassure run --snapshot-id

wpassure configure

-----------------------------------------------



wpassure run --db_host=localhost
	- copy wpassure.json to /wp-content/.wpassure/
	- wpsnapshots create
			 Saves sql and files at /wp/.wpsnapshots/SNAPID/* (does not push remotely)
	- spin up php, mysql and nginx container
	- within container, wpsnapshots pull SNAPID
	- Setup WP core, generate wp-config.php
	- Finish and output test package snapshot ID
	--lock parameter saves snapshot ID to wpassure.json
	- This test package snapshot has been saved locally? Do you want to push it to the remote repository? `wpsnapshots push --snapshot-id=<snapshot-id>`


wpassure run --snapshot-id=356etg3r45erw
	- spin up php, mysql and nginx container
	- within container, wpsnapshots pull SNAPID
	- run tests at /wp-content







Remote Workflow:

- wpsnapshots push (send to S3)
- (inside server) wpsnapshots pull
- wpassure --db_host=localhost


Example wpassure.json:

{
	"snapshot-id": "8sdoh2tsld223ttsd",
	"tests": "tests/js/*"
}
