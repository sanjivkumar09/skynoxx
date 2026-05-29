# Entity-Relationship Diagram (ERD) for Free Fire Tournament Platform

## Overview
This document outlines the Entity-Relationship Diagram (ERD) for the Free Fire Tournament Platform. The ERD illustrates the relationships between different entities in the database, which are crucial for managing users, tournaments, registrations, payments, and announcements.

## Entities

### 1. Users
- **Attributes:**
  - id (Primary Key)
  - name
  - email
  - phone
  - role (player/creator/admin)
  - password
  - joined_at

### 2. Tournaments
- **Attributes:**
  - id (Primary Key)
  - title
  - description
  - entry_fee
  - prize_pool
  - max_players
  - match_type
  - map_name
  - date
  - time
  - room_id
  - room_password
  - created_by (Foreign Key referencing Users)
  - status
  - created_at

### 3. Registrations
- **Attributes:**
  - id (Primary Key)
  - tournament_id (Foreign Key referencing Tournaments)
  - player_id (Foreign Key referencing Users)
  - payment_status
  - prize_won
  - joined_at

### 4. Payments
- **Attributes:**
  - id (Primary Key)
  - user_id (Foreign Key referencing Users)
  - tournament_id (Foreign Key referencing Tournaments)
  - amount
  - method
  - txn_id
  - status
  - created_at

### 5. Withdrawals
- **Attributes:**
  - id (Primary Key)
  - creator_id (Foreign Key referencing Users)
  - amount
  - upi_id
  - status
  - requested_at

### 6. Announcements
- **Attributes:**
  - id (Primary Key)
  - title
  - message
  - posted_by (Foreign Key referencing Users)
  - created_at

## Relationships
- **Users to Tournaments:** One-to-Many (A creator can create multiple tournaments)
- **Users to Registrations:** One-to-Many (A player can register for multiple tournaments)
- **Tournaments to Registrations:** One-to-Many (A tournament can have multiple registrations)
- **Users to Payments:** One-to-Many (A user can make multiple payments)
- **Tournaments to Payments:** One-to-Many (A tournament can have multiple payments)
- **Users to Withdrawals:** One-to-Many (A creator can have multiple withdrawal requests)
- **Users to Announcements:** One-to-Many (An admin can post multiple announcements)

## Diagram
(Insert ERD diagram here)

This ERD serves as a blueprint for the database structure and will guide the development of the Free Fire Tournament Platform.