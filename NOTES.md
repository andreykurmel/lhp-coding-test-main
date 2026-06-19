#Notes regarding the decisions
- I've converted lat/long pairs in the seeder to arrays with country, city, address to prevent a lot of API calls.
- ResolveEventLocationAction can be used in the "CreateEvent" action. Or event Geocoder API with search bar, so user will be able to easily add address.

#Possible future tasks
- Implement create/edit pages for Events with image handling.

#What can be improved in the present architecture
- Database: "Json" column type instead of "Text" for the "Events" table ("Payload" column)
