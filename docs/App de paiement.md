# BILAN DU PROJET ET CAHIER DES CHARGES TECHNIQUE
## Application de paiement avec partage automatique des revenus

**Version :** 1.1 — Document de cadrage initial  
**Date :** 19 octobre 2026  
**Pays de lancement :** Cameroun et Nigeria  
**Extension prévue :** autres pays et devises  
**Nom de l'application :** À définir  
**Entreprise porteuse :** À préciser  

---

# PARTIE I — BILAN DU PROJET

## 1. Résumé exécutif

Le projet consiste à développer une application mobile et une plateforme d'administration permettant aux utilisateurs d'effectuer des paiements auprès de fournisseurs partenaires de l'entreprise.

Chaque paiement confirmé doit permettre de répartir les revenus entre :

- **Le fournisseur partenaire**, qui reçoit la part correspondant à la vente de son produit ou service.
- **L'entreprise exploitant la plateforme**, qui perçoit une commission ou une part définie par contrat.
- **Le prestataire de paiement**, le cas échéant, qui facture les frais prévus dans son contrat.

L'application doit également permettre de générer un reçu pour chaque transaction, de conserver un historique complet des opérations et de suivre les paiements, les commissions, les reversements et les remboursements.

Le lancement est prévu au Cameroun et au Nigeria, avec une architecture permettant d'ajouter progressivement d'autres pays, devises et moyens de paiement.

### Orientation recommandée

L'application doit, dans un premier temps, utiliser les services de prestataires de paiement autorisés pour traiter les paiements et les reversements. L'entreprise devra faire valider son rôle, ses responsabilités et les flux financiers par des professionnels compétents avant le lancement commercial.

## 2. Problématique

L'entreprise souhaite disposer d'une solution numérique permettant aux clients de payer facilement les fournisseurs avec lesquels elle travaille, tout en recevant automatiquement la part qui lui revient.

Le projet doit résoudre plusieurs problèmes :

- Simplifier les paiements entre clients et fournisseurs.
- Automatiser le calcul des commissions.
- Réduire les erreurs de calcul et de suivi manuel.
- Améliorer la traçabilité des opérations financières.
- Fournir une preuve de chaque transaction.
- Permettre aux clients de retrouver leurs transactions passées.
- Permettre aux fournisseurs de suivre leurs ventes et leurs reversements.
- Donner à l'entreprise une vision centralisée de son activité.
- Préparer l'intégration de nouveaux pays et moyens de paiement.

## 3. Objectifs du projet

### 3.1 Objectif général

Concevoir et développer une application sécurisée permettant aux utilisateurs de payer les fournisseurs partenaires, avec répartition des revenus entre les bénéficiaires, émission systématique de reçus et conservation d'un historique détaillé des transactions.

### 3.2 Objectifs spécifiques

L'application devra :

1. Permettre la création et la gestion des comptes utilisateurs.
2. Afficher les fournisseurs partenaires actifs.
3. Permettre d'initier un paiement auprès d'un fournisseur.
4. Prendre en charge les moyens de paiement disponibles au Cameroun et au Nigeria.
5. Calculer automatiquement la commission de l'entreprise.
6. Déterminer la part revenant au fournisseur.
7. Déclencher ou suivre les reversements par l'intermédiaire du prestataire retenu.
8. Générer un reçu pour chaque transaction, quel que soit son résultat.
9. Conserver un historique consultable et filtrable des transactions.
10. Permettre aux clients de consulter et de télécharger leurs reçus.
11. Permettre aux fournisseurs de consulter leurs ventes et leurs reversements.
12. Permettre à l'entreprise de contrôler les transactions, commissions, remboursements et écarts financiers.
13. Garantir la sécurité et la confidentialité des données.
14. Faciliter l'intégration de nouvelles devises et de nouveaux pays.

## 4. Principe de fonctionnement

Le fonctionnement général sera le suivant :

1. Le client ouvre l'application.
2. Il sélectionne un fournisseur ou un service partenaire.
3. Il saisit ou confirme le montant à payer.
4. L'application présente le récapitulatif du paiement et les frais connus.
5. Le client choisit un moyen de paiement disponible.
6. Le prestataire de paiement traite l'opération.
7. Le serveur vérifie la confirmation du paiement.
8. Le système calcule la part de l'entreprise et celle du fournisseur.
9. Le partage ou le reversement est exécuté selon les capacités du prestataire et les contrats applicables.
10. Le système enregistre la transaction et son état.
11. **Un reçu associé à la transaction est créé et rendu accessible à l'utilisateur.**
12. **La transaction est ajoutée à l'historique consultable de l'utilisateur et enregistrée dans les espaces de suivi autorisés du fournisseur et de l'entreprise.**

Le système devra distinguer un paiement réussi d'un paiement en attente, échoué, remboursé ou contesté.

## 5. Modèle économique

L'entreprise gagnera des revenus grâce à une commission appliquée aux transactions réalisées auprès des fournisseurs partenaires.

La commission pourra être :

- Un pourcentage du montant payé.
- Un montant fixe par transaction.
- Une combinaison d'un montant fixe et d'un pourcentage.
- Une règle différente selon le fournisseur ou le contrat.

### Exemple illustratif

Pour une transaction de 10 000 XAF avec une commission de 5 % :

| Élément | Montant |
|---|---:|
| Montant payé par le client | 10 000 XAF |
| Commission de l'entreprise | 500 XAF |
| Part du fournisseur avant frais éventuels | 9 500 XAF |
| Total réparti | 10 000 XAF |

Cet exemple ne tient pas compte des frais du prestataire, des taxes, des remboursements ou d'autres retenues éventuelles. Leur traitement devra être défini dans les contrats.

## 6. Faisabilité préliminaire

| Domaine | Évaluation | Condition de réussite |
|---|---|---|
| Technique | Réalisable | Architecture fiable et intégrations API adaptées |
| Commercial | À confirmer | Contrats avec les fournisseurs partenaires |
| Financier | Réalisable sous conditions | Modèle de commission et frais clairement définis |
| Réglementaire | Validation indispensable | Prestataires autorisés et analyse juridique par pays |
| Sécurité | Exigences élevées | Protection des comptes et intégrité des transactions |
| Internationalisation | Réalisable progressivement | Gestion indépendante des pays et devises |

## 7. Risques principaux

- **Risque réglementaire :** les services de paiement et le transfert de fonds peuvent être soumis à autorisation.
- **Risque de fraude :** faux justificatifs, comptes compromis ou paiements contestés.
- **Risque de double transaction :** une même demande pourrait être traitée plusieurs fois sans mécanisme d'idempotence.
- **Risque de reversement :** le paiement du client peut être confirmé alors que le reversement au fournisseur est encore en attente.
- **Risque de change :** le XAF et le NGN doivent être gérés comme des devises distinctes.
- **Risque de rapprochement :** les montants enregistrés dans l'application peuvent différer des relevés du prestataire.
- **Risque lié aux reçus :** un reçu généré avant la confirmation réelle pourrait être interprété à tort comme une preuve de paiement réussi.
- **Risque de données personnelles :** consultation ou divulgation non autorisée de l'historique des transactions.

## 8. Recommandations

- Lancer d'abord un pilote avec un nombre limité de fournisseurs.
- Sélectionner les prestataires de paiement avant de figer l'architecture définitive.
- Vérifier les paiements côté serveur.
- Associer un identifiant unique à chaque transaction.
- Enregistrer tous les événements financiers importants.
- Prévoir des reçus distincts ou clairement différenciés selon l'état de l'opération.
- Ne jamais supprimer une transaction de l'historique après son enregistrement ; utiliser des opérations compensatrices pour les remboursements.
- Réaliser des tests de sécurité, de paiement, de rapprochement et de restauration avant le lancement.

---

# PARTIE II — CAHIER DES CHARGES FONCTIONNEL

## 9. Présentation générale

### 9.1 Périmètre de la première version

Le produit minimum viable (MVP) comprendra :

- Une application mobile destinée aux clients.
- Une API backend sécurisée.
- Un espace web pour les fournisseurs.
- Un tableau de bord d'administration.
- Un espace de suivi financier.
- Un système de paiement connecté à un ou plusieurs prestataires.
- Un moteur de calcul des commissions.
- Un système de reçus.
- Un historique des transactions.
- Un système de notifications.
- Un mécanisme de rapprochement financier.
- Une gestion des remboursements et des contestations.

### 9.2 Fonctionnalités hors périmètre initial

Sauf décision contraire, la première version ne comprendra pas :

- Un portefeuille électronique interne conservant les fonds des clients.
- Des prêts ou crédits.
- Des transferts libres entre utilisateurs.
- Un service de change directement proposé par l'application.
- Un déploiement simultané dans tous les pays du monde.
- La conservation des codes PIN Mobile Money ou des mots de passe bancaires.
- Le stockage direct des données complètes de cartes bancaires.

## 10. Acteurs et rôles

### 10.1 Client

Le client doit pouvoir :

- Créer un compte et se connecter.
- Consulter les fournisseurs partenaires.
- Initier un paiement.
- Consulter le statut de son paiement.
- Recevoir un reçu associé à chaque transaction.
- Consulter son historique de transactions.
- Rechercher une transaction par référence, période ou fournisseur.
- Télécharger ou partager un reçu.
- Demander de l'aide en cas de problème.
- Consulter les remboursements et les changements de statut.

### 10.2 Fournisseur partenaire

Le fournisseur doit pouvoir :

- Consulter ses informations professionnelles.
- Voir les transactions associées à son compte.
- Consulter les montants bruts, commissions, frais et montants nets.
- Consulter les reversements en attente et confirmés.
- Retrouver les références des transactions.
- Télécharger des relevés selon ses droits.
- Consulter les remboursements ou contestations qui le concernent.

Le fournisseur ne doit pas accéder aux transactions des autres fournisseurs ni aux informations personnelles non nécessaires des clients.

### 10.3 Administrateur

L'administrateur doit pouvoir :

- Créer, modifier, activer ou suspendre des comptes.
- Valider les fournisseurs partenaires.
- Configurer les commissions selon les contrats.
- Activer ou désactiver les moyens de paiement.
- Consulter les transactions autorisées.
- Contrôler les remboursements et reversements.
- Générer des rapports.
- Consulter les journaux d'audit.
- Gérer les réclamations et incidents.
- Configurer les pays, devises et paramètres de la plateforme.

Les opérations financières sensibles devront être soumises à des autorisations spécifiques et, si nécessaire, à une double validation.

### 10.4 Responsable financier

Il devra pouvoir :

- Consulter les commissions.
- Contrôler les reversements.
- Rapprocher les données de la plateforme et celles des prestataires.
- Identifier les écarts.
- Consulter les remboursements et contestations.
- Exporter des rapports comptables.
- Vérifier les opérations de clôture.

## 11. Exigences fonctionnelles détaillées

### EF-01 — Gestion des comptes

- Inscription par téléphone ou e-mail selon le parcours retenu.
- Vérification du moyen de contact.
- Connexion sécurisée.
- Récupération du compte.
- Gestion des sessions.
- Suspension des comptes suspects.
- Authentification renforcée pour les comptes administrateurs.

### EF-02 — Gestion des fournisseurs

Chaque fournisseur devra posséder un profil contenant notamment :

- Un identifiant unique.
- Un nom commercial ou une raison sociale.
- Un pays d'activité.
- Une catégorie d'activité.
- Des coordonnées professionnelles.
- Un statut de validation.
- Une devise de référence ou de règlement.
- Une configuration de commission.
- Une référence de compte marchand ou de bénéficiaire auprès du prestataire, si nécessaire.

Les informations sensibles de règlement devront être gérées de manière sécurisée.

### EF-03 — Initiation d'un paiement

Le client doit pouvoir :

1. Sélectionner un fournisseur.
2. Choisir le produit, le service ou la facture concernée.
3. Saisir ou confirmer le montant.
4. Vérifier le montant total, la devise et les frais connus.
5. Choisir le moyen de paiement.
6. Confirmer sa demande.
7. Suivre l'évolution du paiement.

Chaque demande devra recevoir une référence unique créée côté serveur.

### EF-04 — Moyens de paiement

**Cameroun :**
- Intégration des solutions Mobile Money disponibles auprès du prestataire retenu.
- Prise en charge éventuelle des cartes bancaires et virements si proposés.
- Affichage des moyens réellement disponibles pour le compte marchand de l'entreprise.

**Nigeria :**
- Intégration des moyens locaux proposés par un prestataire autorisé.
- Prise en charge éventuelle des virements bancaires, cartes et autres canaux disponibles.

La disponibilité de chaque moyen devra être confirmée avant de l'annoncer aux clients. Les intégrations devront respecter les exigences propres à chaque pays.

### EF-05 — Calcul des commissions

Le système doit :

- Calculer la commission selon la règle applicable.
- Prendre en charge les commissions fixes, proportionnelles ou combinées.
- Enregistrer la version de la règle utilisée.
- Déterminer la part revenant au fournisseur.
- Gérer les arrondis et frais conformément aux règles définies.
- Empêcher toute modification rétroactive non autorisée.
- Enregistrer les montants attendus et les montants réellement réglés.
- Préserver la cohérence entre le montant payé et les montants répartis.

### EF-06 — Reçu obligatoire pour chaque transaction

**Chaque transaction doit être accompagnée d'un reçu identifiable et consultable.**

Le système devra générer un reçu pour toute transaction enregistrée, y compris les paiements en attente, échoués, annulés ou remboursés. Le reçu devra indiquer clairement le statut réel de l'opération afin d'éviter toute confusion.

Chaque reçu devra contenir, selon les informations disponibles et les exigences légales :

- Le nom de l'application.
- Le nom de l'entreprise exploitante.
- Un numéro de reçu unique.
- La référence unique de la transaction.
- La date et l'heure de création.
- La date et l'heure du paiement, lorsqu'elles sont disponibles.
- Le nom du fournisseur.
- Une description du produit, service ou motif du paiement.
- Le montant de la transaction.
- La devise utilisée.
- Le moyen de paiement, sans divulguer de données sensibles.
- Le statut de la transaction.
- Le montant de la commission de l'entreprise, si sa divulgation est prévue.
- Les frais applicables, lorsque cela est nécessaire.
- Le montant net revenant au fournisseur, lorsque cette information doit être communiquée.
- La référence du prestataire, si elle peut être affichée sans risque.
- Les coordonnées du support en cas de problème.

#### Règles de gestion des reçus

1. Le reçu doit être lié à une transaction précise.
2. Le numéro de reçu doit être unique.
3. Un reçu ne doit pas être présenté comme une preuve de paiement réussi tant que le paiement n'a pas été confirmé.
4. Un reçu provisoire devra porter la mention « En attente » lorsque le paiement n'est pas confirmé.
5. En cas d'échec, le reçu devra afficher clairement « Échec » ou le statut équivalent.
6. En cas de remboursement, un reçu ou justificatif de remboursement complémentaire devra être produit, avec une référence liée à la transaction initiale.
7. Le reçu initial ne doit pas être effacé ou modifié silencieusement après un remboursement.
8. Les reçus devront être consultables depuis l'historique de l'utilisateur.
9. L'utilisateur devra pouvoir télécharger ou partager le reçu, par exemple au format PDF, si cette fonctionnalité est retenue.
10. Les reçus devront pouvoir être retrouvés par leur référence.
11. Le fournisseur et l'entreprise devront disposer des reçus ou justificatifs correspondant aux opérations qu'ils sont autorisés à consulter.
12. La durée de conservation des reçus devra respecter les obligations légales et comptables applicables.

### EF-07 — Historique des transactions

**L'application doit disposer d'un historique permanent et consultable des transactions enregistrées, soumis aux règles de conservation et de protection des données applicables.**

L'historique client devra permettre de retrouver toutes les transactions associées à son compte, y compris les paiements en attente, réussis, échoués, annulés et remboursés.

#### Informations affichées

Pour chaque transaction, l'historique devra présenter :

- La référence de la transaction.
- La date et l'heure.
- Le nom du fournisseur.
- La description du paiement.
- Le montant.
- La devise.
- Le moyen de paiement, sous une forme non sensible.
- Le statut.
- La commission, lorsque l'information est pertinente pour l'utilisateur.
- La référence du reçu.
- Le montant remboursé, le cas échéant.

#### Fonctionnalités de l'historique

L'utilisateur devra pouvoir :

- Consulter la liste de ses transactions.
- Ouvrir une transaction pour voir ses détails.
- Retrouver le reçu associé.
- Filtrer les transactions par période.
- Filtrer par fournisseur.
- Filtrer par statut.
- Rechercher par référence.
- Consulter les détails d'un remboursement.
- Télécharger un reçu ou un relevé si cette fonctionnalité est activée.

L'historique fournisseur devra présenter uniquement les transactions qui concernent ce fournisseur.

L'historique de l'entreprise devra permettre une recherche centralisée selon les droits d'accès, avec des filtres par pays, fournisseur, devise, date, statut et référence.

#### Règles de gestion de l'historique

1. Chaque transaction enregistrée doit apparaître dans l'historique correspondant.
2. L'historique doit être mis à jour après chaque changement de statut confirmé.
3. Les transactions ne doivent pas disparaître parce qu'elles ont échoué ou ont été remboursées.
4. Les remboursements et corrections doivent être enregistrés comme des opérations distinctes liées à l'opération initiale.
5. Les utilisateurs ne doivent accéder qu'à leurs propres transactions.
6. Les fournisseurs ne doivent consulter que les opérations qui les concernent.
7. Les accès administratifs doivent être contrôlés et journalisés.
8. Les données doivent être sauvegardées et restaurables.
9. Les durées de conservation et les procédures de suppression des données personnelles doivent respecter la réglementation applicable.
10. Les montants historiques doivent rester associés à la devise et aux règles de commission utilisées au moment de la transaction.

### EF-08 — Notifications

Le système devra notifier l'utilisateur lors des événements pertinents :

- Paiement initié.
- Paiement confirmé.
- Paiement échoué.
- Paiement expiré.
- Remboursement demandé.
- Remboursement confirmé.
- Reversement au fournisseur confirmé, lorsque pertinent.

Les notifications pourront être envoyées dans l'application, par e-mail ou par SMS selon les possibilités retenues.

Une notification ne constitue pas à elle seule une preuve de paiement : le statut de référence reste celui vérifié côté serveur.

### EF-09 — Remboursements et contestations

Le système devra :

- Enregistrer chaque demande de remboursement.
- Vérifier le statut de la transaction initiale.
- Contrôler les autorisations du demandeur et de l'approbateur.
- Enregistrer le motif et le montant du remboursement.
- Suivre le statut du remboursement.
- Produire un justificatif associé.
- Mettre à jour l'historique.
- Conserver le lien avec la transaction initiale.
- Tenir compte de la répartition des montants entre le fournisseur et l'entreprise.
- Prévoir un suivi des contestations et des demandes d'assistance.

Les règles d'approbation et la répartition des pertes ou frais liés aux remboursements devront être définies contractuellement.

### EF-10 — Rapports et rapprochement financier

Le système devra fournir des rapports sur :

- Le nombre de transactions.
- Le volume total des paiements.
- Les commissions générées.
- Les frais des prestataires.
- Les montants dus aux fournisseurs.
- Les reversements en attente.
- Les reversements confirmés.
- Les paiements échoués.
- Les remboursements.
- Les contestations.
- Les écarts entre les données internes et les relevés des prestataires.

Un mécanisme de rapprochement devra permettre d'identifier les opérations manquantes, répétées ou présentant un écart de montant.

---

# PARTIE III — CAHIER DES CHARGES TECHNIQUE

## 12. Architecture générale

L'architecture recommandée est modulaire, afin de permettre d'ajouter des moyens de paiement ou des pays sans modifier profondément l'application.

```text
Application mobile client
          |
          v
API backend sécurisée
          |
          +---- Gestion des comptes
          |
          +---- Gestion des fournisseurs
          |
          +---- Gestion des commandes
          |
          +---- Service de paiement
          |          |
          |          v
          |     Adaptateurs de prestataires
          |          |
          |          v
          |     Banques / Mobile Money / autres
          |
          +---- Moteur de commissions
          |
          +---- Service de reçus
          |
          +---- Historique des transactions
          |
          +---- Journal financier et rapprochement
          |
          +---- Notifications
          |
          +---- Base de données
          |
          +---- Portail fournisseur
          |
          +---- Tableau de bord administrateur
```

## 13. Technologies recommandées

| Couche | Technologie indicative | Rôle |
|---|---|---|
| Application mobile | Flutter | Interface client |
| Backend | Laravel API ou NestJS | Logique métier et API |
| Base de données | PostgreSQL | Comptes, transactions et historiques |
| Portail web | React ou Blade/Laravel | Administration et espace fournisseur |
| File de tâches | Redis et système de files | Traitements asynchrones |
| Reçus | Génération PDF côté serveur | Production des justificatifs |
| Stockage | Stockage privé sécurisé | Conservation des documents si nécessaire |
| Hébergement | Infrastructure managée ou cloud | Disponibilité, sauvegardes et surveillance |
| Sécurité | TLS, contrôle d'accès, journalisation | Protection des données et opérations |

Une combinaison Flutter + Laravel + PostgreSQL constitue une option cohérente pour le premier produit. Le choix définitif devra tenir compte des compétences de l'équipe et des SDK/API du prestataire retenu.

## 14. Modèle de données initial

### 14.1 Table `users`

Contient les informations de compte :

- `id`
- `name`
- `email`
- `phone`
- `password_hash`
- `status`
- `created_at`
- `updated_at`

Les champs devront être adaptés aux exigences de confidentialité et aux parcours de vérification retenus.

### 14.2 Table `suppliers`

Contient :

- `id`
- `business_name`
- `country_code`
- `category`
- `status`
- `settlement_currency`
- `created_at`
- `updated_at`

### 14.3 Table `orders`

Contient les informations commerciales :

- `id`
- `reference`
- `user_id`
- `supplier_id`
- `description`
- `amount`
- `currency`
- `status`
- `created_at`

### 14.4 Table `payments`

Contient les informations de paiement :

- `id`
- `order_id`
- `provider`
- `provider_reference`
- `amount`
- `currency`
- `payment_method`
- `status`
- `confirmed_at`
- `created_at`
- `updated_at`

### 14.5 Table `commission_rules`

Contient :

- `id`
- `supplier_id`, si une règle particulière est applicable
- `commission_type`
- `commission_value`
- `currency`, si nécessaire
- `valid_from`
- `valid_until`
- `version`
- `status`

### 14.6 Table `allocations`

Contient les montants calculés pour chaque bénéficiaire :

- `id`
- `payment_id`
- `beneficiary_type`
- `beneficiary_id`
- `gross_amount`
- `fees`
- `net_amount`
- `currency`
- `status`

### 14.7 Table `receipts`

Contient les informations nécessaires à la traçabilité des reçus :

- `id`
- `receipt_number`
- `transaction_id`
- `receipt_type`
- `status`
- `issued_at`
- `document_reference`, si un PDF est conservé
- `created_at`

Le reçu devra être lié à la transaction correspondante et ne devra pas être considéré comme une preuve de paiement réussi lorsque la transaction est encore en attente.

### 14.8 Table `transaction_history`

Cette table, ou un mécanisme équivalent d'historique des événements, devra conserver les changements importants :

- `id`
- `transaction_id`
- `previous_status`
- `new_status`
- `event_type`
- `event_reference`
- `event_timestamp`
- `actor_type`
- `actor_id`, lorsque pertinent

Elle permettra de comprendre l'évolution de chaque transaction sans écraser les événements précédents.

### 14.9 Tables complémentaires

- `refunds` : demandes et résultats des remboursements.
- `settlements` : reversements et états de règlement.
- `payment_events` : événements reçus des prestataires.
- `ledger_entries` : écritures financières traçables.
- `notifications` : notifications envoyées.
- `audit_logs` : journal des actions administratives.
- `countries` : pays activés.
- `currencies` : devises prises en charge.
- `payment_methods` : moyens de paiement activés.

### 14.10 Règles de stockage monétaire

Les montants devront être stockés dans un format adapté aux calculs financiers, de préférence sous forme d'entiers en unités monétaires mineures lorsque cela convient à la devise concernée.

Il faut éviter les nombres flottants pour calculer les montants de paiement et de commission.

Chaque montant doit toujours être associé à une devise explicite.

## 15. API indicative

Les routes suivantes sont des exemples qui devront être ajustés au modèle technique retenu.

| Méthode | Route | Fonction |
|---|---|---|
| POST | `/api/v1/auth/register` | Inscription |
| POST | `/api/v1/auth/login` | Connexion |
| GET | `/api/v1/suppliers` | Liste des fournisseurs |
| GET | `/api/v1/suppliers/{id}` | Détails d'un fournisseur |
| POST | `/api/v1/orders` | Création d'une commande |
| POST | `/api/v1/orders/{id}/payment-intent` | Initialisation du paiement |
| GET | `/api/v1/payments/{id}` | État d'un paiement |
| GET | `/api/v1/me/transactions` | Historique du client |
| GET | `/api/v1/me/transactions/{id}` | Détails d'une transaction |
| GET | `/api/v1/me/transactions/{id}/receipt` | Consultation du reçu |
| GET | `/api/v1/me/receipts/{id}/download` | Téléchargement du reçu |
| GET | `/api/v1/supplier/transactions` | Historique du fournisseur |
| GET | `/api/v1/supplier/settlements` | Reversements du fournisseur |
| POST | `/api/v1/webhooks/{provider}` | Réception des événements du prestataire |
| POST | `/api/v1/admin/refunds` | Demande de remboursement autorisée |
| GET | `/api/v1/admin/reports/settlements` | Rapport de rapprochement |

Toutes les routes privées devront vérifier l'identité de l'utilisateur et ses autorisations. Les routes de reçus devront vérifier que le demandeur a le droit d'accéder à la transaction correspondante.

## 16. Cycle de vie des transactions

Les statuts internes pourront inclure :

- `CREATED` : demande créée.
- `PENDING` : paiement en attente.
- `REQUIRES_ACTION` : action du client requise.
- `PAID` : paiement confirmé.
- `FAILED` : paiement échoué.
- `EXPIRED` : demande expirée.
- `ALLOCATION_PENDING` : calcul ou répartition en cours.
- `SETTLEMENT_PENDING` : reversement en attente.
- `SETTLED` : reversement confirmé.
- `PARTIALLY_REFUNDED` : remboursement partiel.
- `REFUNDED` : remboursement total confirmé.
- `DISPUTED` : contestation en cours.

Les reçus et l'historique devront refléter ces statuts de manière compréhensible.

Les statuts du prestataire seront traduits vers le modèle interne sans annoncer un paiement réussi avant confirmation fiable.

## 17. Sécurité

Le système devra mettre en œuvre les mesures suivantes :

- Chiffrement des communications par HTTPS/TLS.
- Hachage sécurisé des mots de passe.
- Stockage sécurisé des clés et secrets API.
- Contrôle d'accès par rôle.
- Protection contre les tentatives de connexion répétées.
- Validation de toutes les données reçues.
- Vérification des signatures des webhooks.
- Idempotence des demandes de paiement et des remboursements.
- Journalisation des opérations sensibles.
- Sauvegardes chiffrées et tests de restauration.
- Surveillance des activités inhabituelles.
- Protection des reçus et documents privés.
- Limitation de l'accès aux historiques.
- Absence de données secrètes dans les journaux.
- Environnements séparés pour le développement, les tests et la production.

L'application ne doit jamais demander au client de lui communiquer son code PIN Mobile Money ou son mot de passe bancaire.

Si les cartes bancaires sont acceptées, l'intégration devra être conçue avec le prestataire pour limiter l'exposition des données de carte et respecter les exigences PCI DSS applicables.

## 18. Exigences non fonctionnelles

### 18.1 Performance

L'application devra permettre une consultation rapide de l'historique et des reçus, y compris lorsque le nombre de transactions augmente.

Les traitements secondaires, comme certaines notifications ou générations de rapports, pourront être exécutés de façon asynchrone.

### 18.2 Disponibilité

Le système devra prévoir :

- Une surveillance du service.
- Des alertes en cas d'erreur.
- Une procédure de reprise.
- Des sauvegardes régulières.
- Un mécanisme de reprise des événements retardés.
- Des procédures de rapprochement en cas d'interruption d'un prestataire.

### 18.3 Utilisabilité

L'interface devra être adaptée aux téléphones et aux connexions parfois instables.

Les montants, devises et statuts devront être clairement présentés.

L'application devra prévoir le français au lancement et une stratégie d'internationalisation, notamment pour l'anglais au Nigeria.

### 18.4 Intégrité de l'historique

L'historique financier devra être traçable et protégé contre les modifications non autorisées.

Une transaction ne devra pas être effacée simplement parce qu'elle a été remboursée ou corrigée. Les corrections devront être enregistrées sous forme d'événements ou d'opérations distinctes.

---

# PARTIE IV — CONFORMITÉ ET PRESTATAIRES DE PAIEMENT

## 19. Conformité réglementaire

Cette partie constitue une liste de vérification et non un avis juridique.

### 19.1 Cameroun et zone CEMAC

Il faudra notamment vérifier :

- Le cadre réglementaire des services de paiement de la CEMAC.
- Le Règlement n°04/18/CEMAC/UMAC/COBAC relatif aux services de paiement, ainsi que les textes d'application ou modifications en vigueur.
- Les autorisations nécessaires selon le rôle réel de l'entreprise.
- Les obligations applicables aux prestataires et aux partenaires.
- Les exigences de lutte contre le blanchiment et le financement du terrorisme.
- Les obligations fiscales, comptables et de conservation des documents.
- Les règles de protection des données personnelles et les transferts de données applicables.

### 19.2 Nigeria

Il faudra vérifier :

- Les exigences de la Central Bank of Nigeria concernant les services de paiement.
- Les catégories d'autorisations applicables au modèle retenu.
- La situation réglementaire des prestataires sélectionnés.
- Les obligations de vérification des clients ou fournisseurs, selon le service.
- Les règles de traitement des plaintes, de conservation et de déclaration.
- Les règles de protection des données, de fiscalité et de transfert de fonds.

### 19.3 Conditions préalables au lancement

Avant d'accepter de vrais paiements, l'entreprise devra disposer de :

1. Une validation juridique pour chaque pays.
2. Des contrats avec les fournisseurs partenaires.
3. Des contrats avec les prestataires de paiement.
4. Une confirmation de la faisabilité du partage ou reversement des fonds.
5. Une politique de remboursement et de gestion des contestations.
6. Des conditions générales d'utilisation.
7. Une politique de confidentialité.
8. Des procédures de support et de gestion des incidents.
9. Des tests de sécurité et d'intégrité financière.
10. Une procédure de conservation des transactions, reçus et pièces justificatives.

## 20. Sélection des prestataires de paiement

Le prestataire devra être évalué selon :

- Sa couverture effective du Cameroun et du Nigeria.
- Ses autorisations et conditions d'utilisation.
- Les moyens de paiement proposés dans chaque pays.
- Les devises acceptées.
- La prise en charge réelle du partage de paiements ou d'un mécanisme équivalent.
- Les frais et délais de règlement.
- Les remboursements et contestations.
- Les webhooks sécurisés.
- Les clés d'idempotence.
- La documentation technique et l'environnement de test.
- La qualité du support.
- Les fonctions de rapprochement et d'export comptable.
- Les conditions de gestion des données.
- La compatibilité de ses contrats avec le modèle économique de l'entreprise.

Un seul prestataire ne couvrira pas nécessairement tous les besoins dans les deux pays. Une architecture avec plusieurs adaptateurs pourra donc être nécessaire.

## 21. Références officielles de départ

Les versions en vigueur devront être vérifiées avant la mise en production.

1. **BEAC / COBAC — Règlements de la COBAC**  
   https://www.beac.int/supervision-bancaire/reglements-de-cobac/

2. **Central Bank of Nigeria — Payment Service Providers**  
   https://www.cbn.gov.ng/PaymentsSystem/PSPs.html

3. **Central Bank of Nigeria — Payments System**  
   https://www.cbn.gov.ng/PaymentsSystem/

4. **PCI Security Standards Council — PCI DSS**  
   https://www.pcisecuritystandards.org/standards/pci-dss/

---

# PARTIE V — PLAN DE DÉVELOPPEMENT

## 22. Phases de réalisation

| Phase | Travaux principaux | Livrable |
|---|---|---|
| 1. Cadrage | Définir les besoins, commissions et responsabilités | Cahier des charges validé |
| 2. Conformité | Vérifier les obligations au Cameroun et au Nigeria | Validation réglementaire initiale |
| 3. Prestataires | Comparer les solutions de paiement et tester leur couverture | Prestataire(s) sélectionné(s) |
| 4. Conception | Définir l'architecture, les écrans et la base de données | Maquettes et conception technique |
| 5. Développement du socle | Comptes, rôles, fournisseurs et paramètres | Première version fonctionnelle |
| 6. Paiements | Intégration sandbox, webhooks et commissions | Paiements de test validés |
| 7. Reçus et historique | Génération des reçus, consultation, filtres et téléchargements | Historique et justificatifs opérationnels |
| 8. Portails | Espace fournisseur et tableau de bord administrateur | Gestion des transactions |
| 9. Tests | Sécurité, remboursements, rapprochement et reprise | Rapport de recette |
| 10. Pilote | Lancement limité avec fournisseurs sélectionnés | Retour d'expérience et corrections |
| 11. Extension | Ajout progressif de pays et devises | Nouvelle version validée |

La durée réelle dépendra des ressources disponibles, des validations réglementaires et de la complexité des intégrations. Une échéance ferme devra être définie après le cadrage et les tests initiaux.

## 23. Tests et critères d'acceptation

La première version sera considérée comme acceptable si les tests démontrent que :

1. Un paiement réussi est confirmé côté serveur.
2. Un paiement échoué n'entraîne pas un reversement indu.
3. Une même notification reçue plusieurs fois ne crée pas plusieurs paiements.
4. Les montants et devises sont vérifiés.
5. La commission est calculée selon la règle applicable.
6. Chaque transaction possède une référence unique.
7. Chaque transaction enregistrée possède un reçu associé ou un justificatif correspondant à son statut.
8. Le reçu affiche le statut réel de l'opération.
9. Un utilisateur peut consulter l'historique de ses transactions.
10. Un utilisateur peut retrouver le reçu associé à une transaction.
11. Les filtres de recherche de l'historique fonctionnent.
12. Les transactions remboursées restent traçables.
13. Un fournisseur ne peut pas consulter les transactions d'un autre fournisseur.
14. Les actions administratives sensibles sont contrôlées et journalisées.
15. Le rapprochement permet d'identifier les écarts.
16. Les sauvegardes peuvent être restaurées.
17. Les secrets de paiement ne sont pas exposés.
18. Les paiements réels restent désactivés tant que les validations réglementaires et opérationnelles ne sont pas terminées.

## 24. Indicateurs de performance

Les indicateurs à suivre comprendront :

- Nombre de fournisseurs actifs.
- Nombre de transactions initiées.
- Nombre de transactions réussies.
- Taux de réussite des paiements.
- Volume des paiements par pays et par devise.
- Commissions générées.
- Frais des prestataires.
- Montants reversés aux fournisseurs.
- Délai moyen de reversement.
- Nombre de remboursements.
- Nombre de contestations.
- Nombre d'écarts de rapprochement.
- Nombre de reçus générés.
- Nombre de transactions sans reçu accessible, avec un objectif de zéro.
- Temps moyen de consultation de l'historique.
- Nombre de demandes d'assistance.

## 25. Gouvernance du projet

L'entreprise devra désigner :

- Un responsable métier pour valider les besoins.
- Un responsable financier pour les commissions et rapprochements.
- Un responsable technique pour l'architecture et la sécurité.
- Un interlocuteur juridique ou conformité pour chaque pays.
- Un responsable des relations fournisseurs.
- Un interlocuteur auprès de chaque prestataire de paiement.

Toute modification des commissions, des flux financiers, des moyens de paiement ou des pays devra être documentée, approuvée et testée avant sa mise en production.

## 26. Questions à confirmer avant le développement

Les éléments suivants restent à préciser avec l'entreprise :

1. Quel sera le nom officiel de l'application ?
2. Quelle est la raison sociale de l'entreprise porteuse ?
3. Quels types de produits ou services seront vendus ?
4. L'entreprise est-elle déjà enregistrée au Cameroun, au Nigeria ou dans les deux pays ?
5. La commission sera-t-elle fixe, proportionnelle ou combinée ?
6. Qui supportera les frais du prestataire de paiement ?
7. Le partage doit-il être instantané ou effectué selon un calendrier de règlement ?
8. Les fournisseurs devront-ils disposer de comptes marchands vérifiés ?
9. Quels moyens de paiement doivent être prioritaires dans chaque pays ?
10. L'application sera-t-elle Android uniquement au lancement, ou également disponible sur iOS et sur le web ?
11. Quel est le budget prévu ?
12. Quelle équipe sera chargée du développement et de la maintenance ?
13. Les reçus devront-ils être téléchargeables en PDF, envoyés par e-mail ou disponibles uniquement dans l'application ?
14. Quelle durée de conservation de l'historique et des reçus est requise par les règles applicables ?
15. Quels rapports financiers l'entreprise souhaite-t-elle recevoir quotidiennement ou mensuellement ?

---

# CONCLUSION GÉNÉRALE

Le projet est techniquement réalisable et présente un modèle économique fondé sur la perception d'une commission lors des paiements effectués auprès des fournisseurs partenaires.

Les éléments indispensables de la première version sont :

- Le paiement sécurisé auprès des fournisseurs partenaires.
- Le calcul fiable de la commission de l'entreprise.
- La traçabilité du partage et des reversements.
- **Un reçu associé à chaque transaction, indiquant clairement son statut.**
- **Un historique consultable permettant de retrouver toutes les transactions, leurs détails, leurs statuts et leurs reçus.**
- La gestion des remboursements et contestations.
- La protection des données personnelles et financières.
- La conformité aux exigences applicables au Cameroun et au Nigeria.
- Une architecture évolutive pour l'ajout de nouveaux pays et devises.
