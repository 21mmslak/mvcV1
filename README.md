My README file!
===================

---


# Scrutinizer
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/21mmslak/mvcV1/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/21mmslak/mvcV1/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/21mmslak/mvcV1/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/21mmslak/mvcV1/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/21mmslak/mvcV1/badges/build.png?b=main)](https://scrutinizer-ci.com/g/21mmslak/mvcV1/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/21mmslak/mvcV1/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)

# Black Jack

Ett Symfony projekt som implementerar ett komplett Blackjack med stöd för flera spelare, insatser, splits, dubbla kort och inloggning.

## Funktionalitet

* Spela Black Jack med flera spelare samtidigt
* Lägg individuella bets för varje hand
* Splitta kort när de har samma värde
* Dubbla insatsen och ta ett sista kort
* Dealer spelar automatiskt
* Visuell uppsättning av korten och status på varje hand
* Inloggning och registrering för att koppla speldata till användare
* Scoreboard som visar alla användares coins globalt

## Strukturen i repot

* `src/Controller` – Innehåller kontrollflöden för routing, spelstart, inloggning och scoreboard
* `src/Entity` – Innehåller User och Scoreboard som är ORM entiteter
* `src/Project` – Logik för att starta spel, räkna poäng, hantera spelare och avgöra vinnare
* `templates/` – Twig-templats för spelet och scoreboard
* `tests/` – Tester för att säkerställa att spelets logik fungerar som förväntat

## Kom igång

1. Öppna terminalen
2. $ git clone git@github.com:21mmslak/mvcV1.git
3. $ `composer install`
4. Kör `php bin/console doctrine:migrations:migrate`
5. Starta servern med `symfony server:start`
6. Besök `http://localhost:8000`
