# Conversion
This folder contains the scripts that were used to transform the original database into the new format of ybforum. Those scripts are probably never ever used again.

## Create required databases
Two databases are required. They must both run on the same server (`localhost`) and share a common username and password:
- `old1898`: Contains the original database, restored from a dump
- `ybforum`: Database in the new format of ybforum

Setup the new database as decsribed in [database](../database), but:
- only one user is required (but with full access)
- do not create the initial admin account

Important: Check that the collation is set to `utf8mb4` (note: Probably only relevant for the new database??)

Restore the original database from a dump:
```
mysql -p -u ybforum old1898 < 1898admin.sql
```

## Run the Converter
Configure the database connection parameters for the old and the new database in [ImportConfig.php](ImportConfig.php)
