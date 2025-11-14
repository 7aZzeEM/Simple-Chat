# Simple-Chat
Simple Chat Application with advanced techniques and OOP

# execute this commands

- cd Simple-Chat
- npm install
- composer install
- sqlite3 database/database.db < database/database.sql

u can edit database/database.sql if u want use MySQL not SQLite then edit server/Database.php then edit server/Config.php

# settings

go to server/Config.php then set email and password value,
go to server/WebSocket.php and src/Script/TS/Chat.ts then change IP address for your Private IP address then make port forward in NAT or leave it as it is localhost.

# Run

- npm run serve
