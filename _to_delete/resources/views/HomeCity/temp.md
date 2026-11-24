<div class="location">
    <i class="fa-solid fa-location-dot"></i>
    <h5>{{ $property->location->name ?? 'Unknown Location' }}</h5>
</div>


php artisan make:migration create_property_categories_table --create=property_categories
php artisan make:migration create_house_property_types_table --create=house_property_types
php artisan make:migration create_physical_locations_table --create=physical_locations
php artisan make:migration create_physical_country_table --create=physical_country
php artisan make:migration create_physical_county_table --create=physical_county
php artisan make:migration create_physical_town_table --create=physical_town
php artisan make:migration create_physical_place_table --create=physical_place
php artisan make:migration create_house_unit_types_table --create=house_unit_types
php artisan make:migration create_amenities_table --create=amenities
php artisan make:migration create_house_units_table --create=house_units
php artisan make:migration create_properties_table --create=properties
php artisan make:migration create_listers_table --create=listers
php artisan make:migration create_property_unit_type_images_table --create=property_unit_type_images




php artisan make:seeder CountrySeeder
php artisan make:seeder CountySeeder
php artisan make:seeder TownSeeder
php artisan make:seeder PlaceSeeder
php artisan make:seeder PropertyCategorySeeder
php artisan make:seeder HousePropertyTypeSeeder
php artisan make:seeder AmenitySeeder
php artisan make:seeder PropertySeeder
php artisan make:seeder HouseUnitTypeSeeder
php artisan make:seeder HouseUnitSeeder


Would you like me to generate a full example seeder for properties + units + amenities pivot table as well, so the relationships are populated automatically?
