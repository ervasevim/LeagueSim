# LeagueSimulator Project - Setup and API Documentation

This document explains how to set up and run the LeagueSim project locally using Docker, and describes the available API routes and frontend pages.

---

## Base URL

All API endpoints and frontend routes use the following base URL: http://localhost:36000/

## Getting Started

### 1. Clone the Repository and Setup Environment

Clone the repository and prepare the environment file:

```bash
git clone https://github.com/ervasevim/LeagueSim/
cd LeagueSim
cp .env.example .env
php artisan key:generate
```


### 2. Connect to Docker Container

To connect to the PHP container where you will run PHP commands:

```bash

docker compose up -d --build

docker exec -it leaguesim-fpm bash
```

### 3. Run Database Migrations and Seed

Inside the container, run the following commands to create database tables and seed initial data:

```bash
composer install
php artisan migrate
php artisan db:seed
```

### 4. Frontend Dependencies and Build

Outside the container, in the project root directory, install frontend dependencies and start the development server with:

```bash
npm run dev
```

---

## API Routes

| Method | Endpoint               | Description                           |
|--------|------------------------|-------------------------------------|
| GET    | /api/teams             | Get the list of teams                |
| GET    | /api/fixtures/{week?}  | Get fixtures for a specific week or all weeks if no parameter is given |
| GET    | /api/play-next-week    | Simulate matches for the next week  |
| GET    | /api/play-all-weeks    | Simulate matches for all remaining weeks |
| GET    | /api/standings         | Calculate and return the current league standings |
| GET    | /api/predictions       | Calculate and return championship predictions |
| GET    | /api/reset             | Reset all league data                |

---

## Frontend Routes

| Method | Endpoint     | Description                       |
|--------|--------------|---------------------------------|
| GET    | /teams       | Display the teams page           |
| GET    | /fixtures    | Display the fixtures page        |
| GET    | /simulation  | Display the simulation page      |

---

