# nostrae
# Plateforme de Réservation d'Événements Culturels

Bienvenue sur le dépôt du projet de plateforme web de gestion et réservation d'événements culturels, développé dans le cadre du module NFE114.

## Objectif

Cette application Symfony permet :
- aux visiteurs de consulter les événements culturels,
- aux utilisateurs inscrits de réserver des événements,
- aux organisateurs de publier leurs événements,
- aux administrateurs de gérer la plateforme (utilisateurs, lieux, événements, etc.).

---

## Technologies utilisées

- **Backend** : PHP 8.x avec Symfony
- **Base de données** : MySQL
- **ORM** : Doctrine
- **Frontend** : Bootstrap 5, Chart.js
- **Uploader** : VichUploaderBundle
- **Diagrammes** : MagicDraw, MagicUWE, LucidChart

---

## Prérequis

Avant d’installer le projet, assurez-vous d’avoir :

- PHP >= 8.1
- Composer
- Symfony CLI (facultatif mais recommandé)
- Node.js + Yarn
- MySQL (ou MariaDB)
- Un serveur (Apache, Nginx ou Symfony CLI)

---

## Installation

```bash
git clone https://github.com/votre-utilisateur/nom-du-repo.git
cd nom-du-repo
composer install
yarn install
yarn build
