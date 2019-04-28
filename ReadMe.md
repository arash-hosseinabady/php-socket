TCP Socket messenger :

This is a simple messenger with TCP Socket protocol in PHP.
Installing :

1. run script in `db\database.sql` in your database for create database
2. run script in `db\insertUser.sql` in your database for add user of system. You can change this for insert your user(s)
3. Set database config (host, username, password, db) in `config.php`
4. Go to your shell command-line interface and run:
	
````php -q c:\path\server.php````

5. Using browser, navigate to project location to open chat page!

API
For get list of online user with api, call below link. return list of user(s) in json:
```/?api=getUser```
