# User Data Management API

## Overview

This project is a User Data Management API built with PHP and the Symfony framework using API Platform. It provides a set of APIs to manage user data, interact with a MySQL database, and handle email notifications. The API allows for operations such as uploading user data from a CSV file, viewing stored user data, backing up the database, and restoring the database.

## Features

- **Upload and Store Data API**: Allows an admin to upload a CSV file containing user data. The data is parsed and stored in the database, with notifications sent to users via email.
- **View Data API**: Enables users to retrieve and view all user data stored in the database.
- **Backup Database API**: Allows admins to create a backup of the database, generating a backup file.
- **Restore Database API**: Enables admins to restore the database from a backup file.

## CSV File Format

The CSV file should include the following columns: `name`, `email`, `username`, `address`, and `role`. Below is an example of the CSV data format:
```
name,email,username,address,role
John Doe,john.doe@example.com,johndoe,123 Main St,USER
Jane Smith,jane.smith@example.com,janesmith,456 Elm St,ADMIN
Michael Johnson,michael.j@example.com,mjohnson,789 Pine St,USER
Emily Davis,emily.d@example.com,emilydavis,101 Oak St,ADMIN
David Brown,david.b@example.com,davidbrown,202 Maple St,USER
Sarah Wilson,sarah.w@example.com,sarahwilson,303 Birch St,USER
Daniel Lee,daniel.l@example.com,daniellee,404 Cedar St,ADMIN
Jessica Martinez,jessica.m@example.com,jessicam,505 Walnut St,USER
Paul Garcia,paul.g@example.com,paulgarcia,606 Ash St,USER
Laura Clark,laura.c@example.com,lauraclark,707 Cherry St,ADMIN
```

## Installation

1. **Clone the Repository**

   ```bash
   git clone https://github.com/jatin-dua/user-management.git
   cd user-management/
   ```
2. **Install Dependencies**

    Ensure you have Composer installed, then run:

    ```bash
    composer install
    ```

3. **Configure Environment Variables**
    Include these variables in **.env** or **.env.local** to configure mailer:
    Use this guide as a reference for SMTP: https://www.gmass.co/blog/gmail-smtp/

    ```bash
        MAILER_USER_EMAIL=
        MAILER_DSN_URL=
    ```

4. **Create the Database**

    Run the following command to create the database:

    ```bash
    docker compose up -d
    ```
5. **Make Migrations (First time setup)**
    
    ```bash
    symfony console doctrine:migrations:migrate
    ```

5. **Start the Server**

    Start the Symfony development server:

    ```bash
    symfony server:start -d
    php bin/console messenger:consume -vv

    ```

## API Endpoints

    Upload Data: POST /api/upload
    View Users: GET /api/users
    Backup Database: GET /api/backup
    Restore Database: POST /api/restore

## Note

    - Ensure that your SMTP email settings are properly configured to send emails upon successful data storage.
    - The API is designed to handle CSV files and database interactions, so make sure to validate the CSV format before uploading.
    - Duplicate entries in CSV are not currently handled and will cause the program to crash.
    - use curl (on CLI) to access POST /api/upload
        ```bash
        curl -X POST https://127.0.0.1:8000/api/upload -F "file=@/path/to/data.csv" -F "file_type=text/csv"
        ```
    - This project is build within time constraints and might not follow the industry standard practices and is not optimized for production use.
