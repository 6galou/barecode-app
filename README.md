# Barecode App

Application de gestion de codes-barres pour collection de disques (Laserdiscs, Blu-ray, CD).

## Structure

### `/barecode` - Saisie Rapide
Application pour l'enregistrement rapide des codes-barres.
- Scanner/coller les codes
- Sélectionner le type de disque
- Ajouter des notes rapides
- **Base:** `barcode_db`

### `/barecode-info` - Complément d'Infos
Application pour enrichir les codes-barres avec des informations détaillées via API EAN-Search.
- Récupérer les codes-barres en attente
- Chercher les infos sur EAN-Search
- Compléter les données (titre, auteur, etc.)
- **Base:** `laserdisc_db` (Laserdiscs), `bluray_db` (Blu-ray), `cd_db` (CD)

## Installation

1. **Cloner le repo** dans `/Applications/MAMP/htdocs/barecode-app`
2. **Lancer les setups:**
   - `http://localhost/barecode-app/barecode/setup.php`
   - `http://localhost/barecode-app/barecode-info/setup.php`
3. **Accéder aux apps:**
   - Saisie: `http://localhost/barecode-app/barecode/`
   - Info: `http://localhost/barecode-app/barecode-info/`

## API EAN-Search

- **Version Gratuite:** ~100 requêtes/jour (suffisant pour tester)
- **Version Payante:** À partir de 2000 requêtes/mois (pour CD)
- **Config:** Éditer `barecode-info/config.php` avec ta clé API

## TODO

- [ ] Intégration API EAN-Search
- [ ] Base pour Blu-ray (`bluray_db`)
- [ ] Base pour CD (`cd_db`)
- [ ] Export CSV/PDF
- [ ] Statistiques par format
