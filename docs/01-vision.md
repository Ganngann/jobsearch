# Forem Matcher AI - Vision & Tech Stack

## 1. Vision du Projet
**Forem Matcher AI** est un outil conçu pour automatiser et optimiser la recherche d'emploi sur la plateforme du Forem (Belgique). Le service interroge directement l'API interne du Forem, analyse les annonces grâce à l'IA (Gemini 2.5 Flash) et calcule un score de correspondance personnalisé pour chaque utilisateur.

## 2. Pile Technique (Tech Stack)
- **Framework** : Laravel 11 (PHP 8.2+)
- **Base de données** :
    - *Développement* : SQLite
    - *Production (o2switch)* : MySQL
- **Intelligence Artificielle** : API Google Gemini 2.5 Flash
- **Source de données** : API Interne Forem (Discovery & Detail)
- **Interface** : Tailwind CSS + Blade
- **Hébergement** : o2switch
