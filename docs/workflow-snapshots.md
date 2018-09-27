# Workflow and Snapshots

There are two scenarios or workflows for running WP Assure:

1. Testing a codebase using your local environment (files and database).
2. Testing a codebase against a "primary" snapshot.

The power of WP Assure is working with a team that is all testing it's code against one *primary snapshot*. Of course, in order for this to be successful the primary snapshot must be kept relevant which is the responsiblity of the development team. For example, when new content types are added, content should be added and a new primary snapshot created.

To test a codebase on your local environment, you would run the following command in the directory of `wpassure.json`:
```
wpassure run --local --save
```

The `--local` flag will force WP Assure to ignore a snapshot ID defined in `wpassure.json`. The `--save` flag will make WP Assure create a new snapshot from your local and save the ID to `wpassure.json` (overwritting any old ID). After saving a new primary snapshot to `wpassure.json`, you will want to commit and push the change upstream.

To test a codebase on a primary snapshot, you would simply run the following command in the directory of `wpassure.json`:
```
wpassure run
```

You can only run WP Assure against snapshots that contain some version of the codebase your are testing. This means the snapshot must contain `wpassure.json` with the same `name` as the one you are running.
