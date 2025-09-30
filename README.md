# IntimaTrack (Demo)

A private, wellness‑focused journal for tracking intimate experiences and relationship insights. Built with PHP, MySQL, and a clean, professional UI similar to health trackers.

## Positioning & Categories
- **App Store categories:** Health & Fitness, Lifestyle
- **Tone:** Professional, empowering, non‑explicit
- **Use cases:** Personal reflection, couples’ communication, therapy‑aligned journaling

## Core Features
- **Encounter Logging:** date/time, location, intensities (physical & emotional 1–10), overall rating (1–5), participants (multi‑select), scenario tags (incl. committed‑partner‑present/cuckold), encrypted outcome detail and reflection notes, aftercare/cleanup tracking.
- **Partner Profiles:** name, relationship context, height, build, optional non‑explicit “dimensions note” for health reference, encrypted private notes.
- **Location Tracking:** label, type, coordinates, map timeline.
- **Group Sessions:** add multiple participants per entry.
- **Insights:** charts for partner trends, location averages, and frequency over time.
- **Privacy:** passcode login, field‑level AES‑256‑GCM encryption, no third‑party sharing.

## Setup
1. Create database and tables:
   ```sql
   SOURCE config/schema.sql;
   ```
2. Copy config: `cp config/config.sample.php config/config.php` and set credentials. Generate a strong encryption key:
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
   Put that in `encryption_key_hex`.
3. Serve `public/` via Apache/Nginx (enable HTTPS in production). First user can self‑register at `/public/login.php`.

## Wireframes (ASCII)

### Dashboard
```
+-----------------------------------------------------------+
|  IntimaTrack     [Encounters] [Partners] [Insights] [⚙]  |
+-----------------------------------------------------------+
|  Summary: Encounters • Partners • Last 7 days             |
|  [Log Encounter]                                          |
|                                                           |
|  Map (markers for geotagged entries)                      |
|                                                           |
|  Timeline (Recent 10)                                     |
|   • Mar 3, 7:40pm  Home   P:7 E:8  Participants:2 [Open]  |
|   • Mar 1, 10:10pm Hotel  P:6 E:7  Participants:3 [Open]  |
+-----------------------------------------------------------+
```

### Log Encounter
```
Date/Time [____]  Location label [____]  Type [Home ▾]
Lat [____]  Lng [____]
Physical 1–10 [__]  Emotional 1–10 [__]  Overall 1–5 [__]

Participants [multi-select list]

Scenario [Standard ▾ | Cuckold (observer) | ...]
Outcome detail (encrypted) [______________]
Aftercare notes (encrypted) [_____________]
[ ] Cleanup / aftercare performed   Method [Shower ▾]

Summary (encrypted)
[Save] [Cancel]
```

### Partners
```
Name [____]  Relationship [____]  Height(cm) [__] Build [ ▾ ]
Dimensions note [____________________]  Notes (encrypted)
[Save]
```

### Insights
```
[Bar] Intensity by Partner: Physical | Emotional | Overall
[Bar] Top Locations: Physical avg | Emotional avg
[Line] Timeline Frequency
```

## Marketing Copy (Samples)

- **Headline:** “Strengthen connection through mindful reflection.”  
- **Subhead:** “A private, secure journal to understand patterns, preferences, and emotional wellbeing — for individuals and couples.”
- **Bullets:**
  - Track experiences with context: partners, location, and feelings.
  - Discover patterns with clear, wellness‑oriented insights.
  - Field‑level encryption and local‑first design for peace of mind.
  - Flexible for different relationship structures (e.g., committed‑partner‑present), framed with respect and consent.

## Security Notes
- Sensitive fields use AES‑256‑GCM with a user‑provided key.
- Passwords stored with `password_hash()` (bcrypt/argon2, depending on PHP).
- CSRF tokens on forms, session hardening, Content‑Security‑Policy header.
- Configure HTTPS, secure cookies, and regular OS/database updates in production.
