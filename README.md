# Job Matching Platform

A modern web platform designed to parse resumes, analyze candidate profiles, and seamlessly match them with the best-fitting job opportunities.

## Project Domain
The application functions as an intelligent career gateway:
- **Resume Parsing:** Uses `ResumeParserService` to intelligently extract candidates' information.
- **Profile Mapping:** Connects traits and qualifications via `AIProfileService` and `ProfileMappingService`.
- **API Integrations:** Extends functionality and opportunities via the `ForemApiService`.
- **Job Matching:** Matches candidates to optimal roles using `JobMatcherService` and `MatchingService`.

## Tech Stack
- **Framework:** Laravel 13
- **Language:** PHP 8.3
- **Frontend Tools:** Alpine.js, Tailwind CSS, Vite

## Setup and Development

Follow these steps to set up the project locally.

### Prerequisites
- PHP 8.3 or higher
- Node.js & npm
- Composer

### Installation
1. Clone the repository.
2. Run the automated setup script to configure the environment, install dependencies, set application keys, migrate the database, and build frontend assets:
   ```bash
   composer setup
   ```

### Running the Application
To run the application locally for development, execute:
```bash
composer run dev
```
This single command spins up multiple processes (via concurrently) including the local server, queue listener, log viewer, and Vite's frontend server.

### Testing
Run tests to verify the setup:
```bash
composer run test
```
