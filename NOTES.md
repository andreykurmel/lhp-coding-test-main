#Notes regarding the decisions
- I've converted lat/long pairs in the seeder to arrays with country, city, address to prevent a lot of API calls.
- ResolveEventLocationAction can be used in the "CreateEvent" action. Or event Geocoder API with search bar, so user will be able to easily add an address.
- I would prefer Notifiable trait and send emails via notifications, instead of "Mail::to()", it can be easily fixed by additional rules, but I've reached today's limits and it is not a production code.
- Attendee model is not related to users. It is good for guests, but should be changed if we're going to allow this function to registered users only.
- In general it has been created in ~2-3hrs and we have a lot of places for future updates and improvements (permissions, security and so on).

#Possible future tasks
- Implement create/edit pages for Events with image handling.

#What can be improved in the present architecture
- Database: "Json" column type instead of "Text" for the "Events" table ("Payload" column)
