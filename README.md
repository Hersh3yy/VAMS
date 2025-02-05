# VAMS - Visual Album Management System

## Tech Stack

- PHP 8.3
- Laravel 11
- PostgreSQL
- Docker & Docker Compose
- OpenAI GPT
- PHPUnit for testing
- Scramble for API documentation

## Run Locally with Docker

1. Clone the repository:
```bash
git clone https://github.com/yourusername/work-it-out
cd work-it-out
```

2. Create environment files:
```bash
# Laravel directory
cp src/.env.example src/.env
```

3. Update src/.env with required settings:
```env

OPENAI_API_KEY=your_api_key_here
OPENAI_ORGANIZATION-your-organization # optional
```

4. Install dependencies and build:
```bash
docker compose run --rm api composer install
docker compose build
```

5. Setup application:
```bash
docker compose run --rm api php artisan key:generate
```

6. Start the application:
```bash
docker compose up -d
```

7. Setup database with seed data (optional):
```bash
docker compose run --rm api php artisan migrate:fresh --seed
```

## API Endpoints

### Core Endpoints
- `GET /api/workouts` - List all workouts
- `POST /api/workouts` - Create a new workout
- `GET /api/workouts/{id}` - Get a specific workout
- `DELETE /api/workouts/{id}` - Delete a workout

- `GET /docs/api` - API Documentation UI
- `GET /telescope` - Development debugging dashboard

## Testing

Run the test suite:
```bash
docker compose exec api php artisan test
```

## API Documentation

Access the auto-generated API documentation:
- UI Documentation: http://localhost:8000/docs/api
- OpenAPI Spec: http://localhost:8000/docs/api.json

Development Tools: http://localhost:8000/telescope


## Example Response Format

```json
{
    "workout": {
        "id": 1,
        "raw_input": "Did 3 sets of bench press: 100kg for 5 reps, 110kg for 3, 120kg for 1",
        "parsed_data": {
            "exercises": [
                {
                    "name": "Bench Press",
                    "sets": [
                        {
                            "reps": 5,
                            "weight": 100
                        },
                        {
                            "reps": 3,
                            "weight": 110
                        },
                        {
                            "reps": 1,
                            "weight": 120
                        }
                    ]
                }
            ]
        },
        "performed_at": "2024-02-03T21:00:00.000000Z",
        "created_at": "2024-02-03T21:00:00.000000Z",
        "updated_at": "2024-02-03T21:00:00.000000Z"
    }
}
```


![planned ERD (could chnage slightly)](https://github.com/Hersh3yy/work-it-out/blob/main/work-it-out.drawio.png?raw=true)
