#!/bin/sh

# folders and files
backupfolder="/srv/www/vhosts/1898.ch/private/dbbackups"
maxfiles="7"
matchpattern="sql.dump"
dumpextensions="sql.dump"
compressextension="gz"

# db parameters
host="server09.hostfactory.ch"
port="3306"
user="dbybforum_reader"
pass="passwort"
database="dbybforum"

# first check that we do not exceed the number of files in the backup directory
nrofbackups="$(ls -l ${backupfolder} | grep -c ${matchpattern})"
if [ "$nrofbackups" -gt "$maxfiles" ]; then
    >&2 echo "ERROR: To many backup files, alreday ${nrofbackups} in ${backupfolder}, aborting"
    exit 1
fi

# calculate filename of the today file:
now="$(mysql --host=$host --port=$port -u $user -p$pass $database -ss -e 'SELECT DATE_FORMAT(NOW(), "%Y-%m-%d");')"

# if something with the today date already exists, abort
nrofmatchingnowfiles="$(ls -l ${backupfolder} | grep -c ${now})"
if [ "$nrofmatchingnowfiles" -gt "0" ]; then
    >&2 echo "ERROR: A file matching ${now} already exists in ${backupfolder}, aborting"
    exit 1
fi

# remove everyting that matches the date one week ago
weekago="$(mysql --host=$host --port=$port -u $user -p$pass $database -ss -e 'SELECT DATE_FORMAT(SUBDATE(NOW(), INTERVAL 1 week), "%Y-%m-%d");')"
toremovefilename="${backupfolder}/${database}_${weekago}.${dumpextensions}.${compressextension}"
if [ -f "$toremovefilename" ]; then
    echo "Removing old file ${toremovefilename}"
    rm ${toremovefilename}
fi

# create the new backup file
dumpfile="${backupfolder}/${database}_${now}.${dumpextensions}"
mysqldump --single-transaction --routines --host=$host --port=$port -u $user -p$pass $database > ${dumpfile}

# and zip that file
gzip ${dumpfile}

echo "Created backup of database ${database} in file ${dumpfile}.${compressextension}"
