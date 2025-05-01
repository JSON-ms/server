# @jsonms/server

Welcome to the **@jsonms/server** project!  
This package provides the server-side foundation for managing MySQL schemas and environment configurations for the JSONMS system.

## Requirements
- PHP 8.x
- MySQL server

## Getting Started

Follow these steps to set up and run the project locally:

### 1. Prepare the MySQL Database

A `.datatable.sql` file is included in the project.  
It contains the necessary schema definitions required to run the application.

To set up your database:

```bash
mysql -u your_user -p your_database_name < .datatable.sql
```

Replace your_user and your_database_name with your MySQL username and target database name.

Make sure your MySQL server is running and accessible.

### 2. Set Up Environment Variables
The project uses environment variables for configuration.
A sample file `.env.example` is provided.

To create your local `.env` file:

```bash
cp .env.example .env
```

Edit `.env` to match your environment settings, such as database connection credentials, ports, and other options.

### 3. Running the Server

You can define your own virtual host with Apache or Nginx but the fastest way to run the server is with a built-in PHP server.

```bash
php -S localhost:9001 index.php
```

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open an issue.

## License
This project is licensed under the BSD-3-Clause License. See the LICENSE file for details.