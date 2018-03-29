# Huizen Op Flakkee - WP JSON koppeling

Deze Wordpress plugin genereert een WP-JSON rest api endpoint ten behoeve van de indexatie op Huizen Op Flakkee.

>Note: Deze plugin is nog niet generiek ontwikkeld en behoeft enige kennis van PHP.

## Installatie

* Installeer plugin en activeer deze in Wordpress.
* Open het bestand src/WP_REST_Aanbod_Controller.
* Pas in de constructor de property resource_name aan naar het betreffende post type.
* Pas eventueel in de method 'get_items' de query aan.
* Binnen de method 'get_item_schema' zijn alle velden te zien die nodig zijn in de koppeling.
* Map in de method 'prepare_item_for_response' de velden van het post type naar de velden van de koppeling
* De koppeling zou nu beschikbaar moeten zijn via het pad: '/wp-json/hof/v1/{post_type_naam}'
