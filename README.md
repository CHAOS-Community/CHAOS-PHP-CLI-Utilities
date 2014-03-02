CHAOS-PHP-CLI-Utilities
=======================

Small helpfull tools to use when manipulating objects in a CHAOS service.

# Running a utility
## Setup the environment
Set the three environment variables, CHAOS_URL, CHAOS_EMAIL and CHAOS_PASSWORD.

## Execution examples
Below is a couple of examples on execution of different actions with various filters - any action and filter is surrounded by quotes, as paranthesis otherwise would have to be escaped as ( = \( and ) = \)

### Removing metadata
Remove matadata from any object with metadata validating against the 00000000-0000-0000-0000-000063c30000 and 5906a41b-feae-48db-bfb7-714b3e105396 metadata schemas, in debug mode.

	php src/CHAOS/Utilities/CHAOSUtilityRunner.php \
	--query=FolderID:715 \
	"--filter=HasMetadata(5906a41b-feae-48db-bfb7-714b3e105396)" \
	"--filter=HasMetadata(00000000-0000-0000-0000-000063c30000)" \
	"--action=RemoveMetadata(00000000-0000-0000-0000-000063c30000)" \
	--debug

### Unpublishing objects
Unpublish any object in the folder with id 440, in debug mode.

	php src/CHAOS/Utilities/CHAOSUtilityRunner.php \
	--query=FolderID:440 \
	"--action=UnpublishObject()" \
	--debug
