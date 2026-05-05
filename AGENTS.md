# Agentic Instructions

This project is a job matching platform aiming to parse resumes, map profiles and connect candidates with job offers.

## Tech Stack
- **Framework:** Laravel 13
- **Language:** PHP 8.3
- **Frontend:** Alpine.js, Tailwind CSS, Vite

## Project Architecture & Key Services
- **Resume Parser:** `ResumeParserService` processes user resumes.
- **Profile Mapping:** `AIProfileService` and `ProfileMappingService` map candidate skills and interests.
- **External API:** `ForemApiService` acts as a bridge with external employment services.
- **Job Matcher:** `JobMatcherService` and `MatchingService` calculate compatibility and match candidates with relevant job offers.

## Instructions
- As an AI agent working on this codebase, remember to test changes using `composer test` or `php artisan test`.
- Respect the Laravel 13 ecosystem conventions.
- Maintain a clean and structured architecture when adding new services or endpoints.
