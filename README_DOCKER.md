# Docker Setup Guide for Perfumes Management System

## Prerequisites
- Docker Desktop installed (Windows/Mac) or Docker Engine (Linux)
- Docker Compose (usually included with Docker Desktop)

## Quick Start

### 1. Build and Start Containers
```bash
docker-compose up -d
```

This will:
- Build the PHP Apache web server container
- Start MySQL database container
- Start phpMyAdmin (optional database management tool)
- Create necessary networks and volumes

### 2. Access the Application
- **Web Application**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

### 3. Database Configuration
The application will connect to MySQL automatically. If you need to update database credentials, edit `docker-compose.yml` and update the environment variables.

### 4. Initialize Database
If you have a SQL initialization script, place it in `database/init.sql` and it will run automatically on first startup.

Alternatively, you can import your database manually:
```bash
docker exec -i perfume_db mysql -uroot -prootpassword perfume_db < your_database.sql
```

## Useful Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f db
```

### Stop Containers
```bash
docker-compose down
```

### Stop and Remove Volumes (WARNING: Deletes database data)
```bash
docker-compose down -v
```

### Rebuild Containers
```bash
docker-compose up -d --build
```

### Access Container Shell
```bash
# Web container
docker exec -it perfume_web bash

# Database container
docker exec -it perfume_db bash
```

### Access MySQL CLI
```bash
docker exec -it perfume_db mysql -uroot -prootpassword perfume_db
```

## File Structure
```
.
├── Dockerfile              # Web server container definition
├── docker-compose.yml      # Multi-container orchestration
├── .dockerignore          # Files to exclude from build
└── includes/
    └── db_connect_docker.php  # Docker database config example
```

## Environment Variables
You can customize the setup by modifying environment variables in `docker-compose.yml`:

- `MYSQL_ROOT_PASSWORD`: Root MySQL password
- `MYSQL_DATABASE`: Database name
- `MYSQL_USER`: Database user
- `MYSQL_PASSWORD`: Database user password

## Troubleshooting

### Port Already in Use
If port 8080 or 3306 is already in use, modify the ports in `docker-compose.yml`:
```yaml
ports:
  - "8082:80"  # Change 8080 to available port
```

### Permission Issues
If you encounter permission issues with file uploads:
```bash
docker exec -it perfume_web chown -R www-data:www-data /var/www/html/images/uploads
docker exec -it perfume_web chmod -R 755 /var/www/html/images/uploads
```

### Database Connection Issues
1. Ensure database container is running: `docker ps`
2. Check database logs: `docker-compose logs db`
3. Verify network connectivity: `docker network ls`

## Production Considerations
For production deployment:
1. Change default passwords
2. Use environment variables for sensitive data
3. Enable SSL/TLS
4. Configure proper backup strategy
5. Set up monitoring and logging
6. Use secrets management

## Development Workflow
1. Make code changes locally
2. Changes are reflected immediately (volume mount)
3. Restart containers if needed: `docker-compose restart web`



