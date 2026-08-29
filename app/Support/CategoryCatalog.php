<?php

namespace App\Support;

/**
 * The full two-level store category catalog (35 parents). Used to (re)seed the
 * categories table. Posting forms render parents in a cascading select and the
 * children beside them as an optional subcategory — see partials/category-cascade.
 */
final class CategoryCatalog
{
    /** @return array<string, list<string>> parent name => child names */
    public static function tree(): array
    {
        return [
            'Electronics' => [
                'Mobile Phones', 'Smartphones', 'Tablets', 'Laptops', 'Desktop Computers',
                'Monitors', 'Computer Components', 'Computer Accessories', 'Keyboards & Mice',
                'Printers & Scanners', 'Networking Equipment', 'Routers & Modems',
                'Storage Devices', 'USB Flash Drives', 'External Hard Drives', 'Memory Cards',
                'Cameras', 'Lenses', 'Camera Accessories', 'Drones', 'Smartwatches',
                'Wearable Technology', 'Headphones & Earbuds', 'Speakers', 'Microphones',
                'Projectors', 'TVs', 'Home Theater', 'Gaming Consoles', 'Video Games',
                'Gaming Accessories', 'VR/AR Devices', 'Chargers & Cables', 'Power Banks',
                'Batteries', 'Smart Home Devices', 'Security Cameras', 'Smart Lighting',
                'Electronic Components',
            ],
            'Fashion & Apparel' => [
                "Men's Clothing", "Women's Clothing", "Kids' Clothing", 'Baby Clothing',
                'T-Shirts', 'Shirts', 'Blouses', 'Tops', 'Jeans', 'Trousers', 'Pants',
                'Shorts', 'Skirts', 'Dresses', 'Suits', 'Blazers', 'Jackets', 'Coats',
                'Sweaters', 'Hoodies', 'Sweatshirts', 'Activewear', 'Sportswear',
                'Swimwear', 'Underwear', 'Lingerie', 'Sleepwear', 'Traditional Clothing',
                'Ethnic Wear', 'Maternity Wear', 'Plus Size', 'Uniforms', 'Costumes',
                'Clothing Accessories',
            ],
            'Shoes' => [
                "Men's Shoes", "Women's Shoes", "Kids' Shoes", 'Sneakers', 'Running Shoes',
                'Sports Shoes', 'Formal Shoes', 'Casual Shoes', 'Boots', 'Sandals',
                'Slippers', 'Heels', 'Flats', 'Loafers', 'Work Shoes', 'Safety Shoes',
                'Hiking Shoes', 'Shoe Accessories',
            ],
            'Bags & Luggage' => [
                'Backpacks', 'School Bags', 'Laptop Bags', 'Handbags', 'Shoulder Bags',
                'Crossbody Bags', 'Tote Bags', 'Clutches', 'Wallets', 'Purses',
                'Travel Bags', 'Duffel Bags', 'Suitcases', 'Luggage Sets', 'Briefcases',
                'Messenger Bags', 'Cosmetic Bags', 'Bag Accessories',
            ],
            'Jewelry & Accessories' => [
                'Rings', 'Necklaces', 'Earrings', 'Bracelets', 'Anklets', 'Pendants',
                'Brooches', 'Jewelry Sets', 'Fine Jewelry', 'Fashion Jewelry', 'Watches',
                'Sunglasses', 'Eyeglasses', 'Belts', 'Hats', 'Caps', 'Scarves', 'Gloves',
                'Ties', 'Hair Accessories', 'Fashion Accessories',
            ],
            'Beauty & Personal Care' => [
                'Skincare', 'Face Care', 'Body Care', 'Hair Care', 'Makeup', 'Fragrances',
                'Perfumes', 'Deodorants', 'Bath & Shower', 'Oral Care', 'Shaving & Grooming',
                "Men's Grooming", "Women's Grooming", 'Nail Care', 'Beauty Tools',
                'Hair Styling Tools', 'Personal Care Appliances', 'Travel Toiletries',
            ],
            'Health & Wellness' => [
                'Vitamins & Supplements', 'Fitness Nutrition', 'Protein Products',
                'First Aid', 'Medical Supplies', 'Personal Health Devices', 'Thermometers',
                'Blood Pressure Monitors', 'Fitness Trackers', 'Mobility Aids',
                'Orthopedic Supports', 'Health & Wellness Accessories',
            ],
            'Home & Living' => [
                'Furniture', 'Sofas', 'Chairs', 'Tables', 'Beds', 'Mattresses', 'Cabinets',
                'Shelves', 'Home Decor', 'Wall Art', 'Mirrors', 'Rugs & Carpets',
                'Curtains', 'Lighting', 'Lamps', 'Bedding', 'Pillows', 'Blankets',
                'Storage & Organization', 'Home Fragrance', 'Cleaning Supplies',
                'Laundry Supplies', 'Home Improvement', 'Tools', 'Hardware',
                'Safety & Security',
            ],
            'Kitchen & Dining' => [
                'Cookware', 'Pots & Pans', 'Bakeware', 'Kitchen Utensils',
                'Kitchen Storage', 'Food Containers', 'Cutlery', 'Dinnerware', 'Glassware',
                'Mugs & Cups', 'Kitchen Appliances', 'Refrigerators', 'Ovens', 'Microwaves',
                'Blenders', 'Mixers', 'Coffee Machines', 'Kettles', 'Toasters', 'Air Fryers',
                'Water Purifiers', 'Dining Furniture', 'Barware',
            ],
            'Grocery & Food' => [
                'Fresh Produce', 'Fruits', 'Vegetables', 'Meat', 'Seafood', 'Dairy',
                'Eggs', 'Bakery', 'Snacks', 'Beverages', 'Coffee', 'Tea', 'Soft Drinks',
                'Packaged Foods', 'Canned Foods', 'Frozen Foods', 'Spices', 'Sauces',
                'Cooking Oils', 'Rice & Grains', 'Pulses & Legumes', 'Pasta & Noodles',
                'Sweets & Desserts', 'Organic Foods', 'Baby Food', 'International Foods',
            ],
            'Baby & Kids' => [
                'Baby Clothing', "Kids' Clothing", 'Diapers', 'Baby Feeding',
                'Baby Bottles', 'Baby Care', 'Baby Furniture', 'Strollers', 'Car Seats',
                'Baby Carriers', 'Toys', 'Educational Toys', 'Remote-Control Toys',
                'Dolls', 'Action Figures', 'Building Sets', 'Puzzles', 'Board Games',
                'Outdoor Toys', 'School Supplies', "Kids' Accessories",
            ],
            'Toys & Games' => [
                'Action Figures', 'Dolls', 'Collectibles', 'Building Toys',
                'Educational Toys', 'STEM Toys', 'Puzzles', 'Board Games', 'Card Games',
                'Strategy Games', 'Outdoor Games', 'RC Vehicles', 'Model Kits',
                'Pretend Play', 'Musical Toys', 'Party Games', 'Video Games',
                'Gaming Accessories',
            ],
            'Sports & Fitness' => [
                'Exercise Equipment', 'Cardio Equipment', 'Strength Training', 'Yoga',
                'Running', 'Cycling', 'Football', 'Cricket', 'Basketball', 'Tennis',
                'Badminton', 'Swimming', 'Hiking', 'Camping', 'Fishing',
                'Outdoor Recreation', 'Sportswear', 'Sports Shoes', 'Protective Equipment',
                'Fitness Accessories', 'Sports Nutrition',
            ],
            'Automotive' => [
                'Cars', 'Motorcycles', 'Electric Vehicles', 'Car Parts',
                'Motorcycle Parts', 'Tires', 'Wheels & Rims', 'Batteries', 'Engine Parts',
                'Brake Parts', 'Suspension', 'Lighting', 'Car Electronics',
                'Audio Systems', 'GPS & Navigation', 'Car Care', 'Cleaning Products',
                'Interior Accessories', 'Exterior Accessories', 'Motorcycle Accessories',
                'Tools & Equipment',
            ],
            'Books & Media' => [
                'Books', 'Fiction', 'Non-Fiction', 'Academic Books', 'Textbooks',
                "Children's Books", 'Comics', 'Magazines', 'Newspapers', 'E-books',
                'Audiobooks', 'Music', 'CDs', 'Vinyl Records', 'Movies', 'DVDs',
                'Educational Media',
            ],
            'Office & Stationery' => [
                'Pens', 'Pencils', 'Notebooks', 'Diaries', 'Paper', 'Files & Folders',
                'Art Supplies', 'School Supplies', 'Office Supplies', 'Desk Accessories',
                'Calculators', 'Printers', 'Ink & Toner', 'Office Furniture',
                'Presentation Supplies', 'Packaging Supplies',
            ],
            'Tools & Industrial' => [
                'Hand Tools', 'Power Tools', 'Measuring Tools', 'Electrical Tools',
                'Plumbing Tools', 'Automotive Tools', 'Workshop Equipment',
                'Welding Equipment', 'Safety Equipment', 'Industrial Machinery',
                'Construction Equipment', 'Fasteners', 'Hardware', 'Electrical Supplies',
                'Plumbing Supplies', 'Cleaning Equipment', 'Material Handling',
            ],
            'Garden & Outdoor' => [
                'Gardening Tools', 'Seeds', 'Plants', 'Pots & Planters', 'Fertilizers',
                'Pest Control', 'Irrigation', 'Outdoor Furniture', 'BBQ & Grills',
                'Outdoor Lighting', 'Camping', 'Patio Accessories', 'Lawn Equipment',
                'Garden Decor',
            ],
            'Pet Supplies' => [
                'Dog Supplies', 'Cat Supplies', 'Bird Supplies', 'Fish Supplies',
                'Small Animal Supplies', 'Pet Food', 'Treats', 'Pet Toys',
                'Beds & Furniture', 'Collars & Leashes', 'Grooming', 'Pet Hygiene',
                'Aquariums', 'Pet Carriers', 'Training Supplies', 'Pet Health Products',
            ],
            'Arts, Crafts & Hobbies' => [
                'Painting', 'Drawing', 'Sketching', 'Craft Supplies', 'Sewing',
                'Knitting', 'Crochet', 'Embroidery', 'Scrapbooking', 'Candle Making',
                'Soap Making', 'Jewelry Making', 'Model Building', 'Collectibles',
                'Musical Instruments', 'Photography', 'DIY Kits',
            ],
            'Musical Instruments' => [
                'Guitars', 'Pianos & Keyboards', 'Drums', 'Percussion', 'Violins',
                'String Instruments', 'Wind Instruments', 'Brass Instruments',
                'DJ Equipment', 'Studio Equipment', 'Amplifiers', 'Speakers',
                'Microphones', 'Music Accessories', 'Instrument Cases',
            ],
            'Appliances' => [
                'Refrigerators', 'Freezers', 'Washing Machines', 'Dryers', 'Dishwashers',
                'Air Conditioners', 'Fans', 'Heaters', 'Vacuum Cleaners', 'Air Purifiers',
                'Water Purifiers', 'Irons', 'Sewing Machines', 'Kitchen Appliances',
                'Small Appliances',
            ],
            'Home Appliances & Smart Home' => [
                'Smart Lights', 'Smart Plugs', 'Smart Speakers', 'Smart Displays',
                'Smart Thermostats', 'Smart Locks', 'Smart Doorbells', 'Security Systems',
                'Robot Vacuums', 'Home Automation', 'Sensors', 'Energy Monitoring',
            ],
            'Travel' => [
                'Luggage', 'Travel Bags', 'Travel Accessories', 'Travel Organizers',
                'Neck Pillows', 'Travel Adapters', 'Passport Holders', 'Travel Bottles',
                'Camping Equipment', 'Hiking Equipment', 'Outdoor Gear',
                'Travel Electronics',
            ],
            'Religious & Cultural Products' => [
                'Religious Books', 'Prayer Accessories', 'Traditional Clothing',
                'Cultural Decor', 'Religious Decor', 'Festival Supplies',
                'Traditional Crafts', 'Cultural Gifts',
            ],
            'Gifts & Occasions' => [
                'Birthday Gifts', 'Wedding Gifts', 'Anniversary Gifts',
                'Graduation Gifts', "Valentine's Gifts", "Mother's Day", "Father's Day",
                'Christmas', 'Eid', 'Diwali', 'Corporate Gifts', 'Personalized Gifts',
                'Gift Cards', 'Flowers', 'Gift Baskets', 'Party Supplies',
                'Decorations',
            ],
            'Luxury & Premium' => [
                'Luxury Fashion', 'Designer Bags', 'Luxury Watches', 'Fine Jewelry',
                'Premium Beauty', 'Luxury Home Decor', 'Premium Electronics',
                'Collectibles', 'Antiques',
            ],
            'Collectibles & Memorabilia' => [
                'Coins', 'Stamps', 'Trading Cards', 'Sports Memorabilia', 'Autographs',
                'Vintage Items', 'Antiques', 'Art Collectibles', 'Toys & Figures',
                'Limited Editions',
            ],
            'Digital Products' => [
                'E-books', 'Online Courses', 'Software', 'Software Licenses',
                'Mobile Apps', 'Templates', 'Digital Art', 'Stock Photos', 'Stock Videos',
                'Music', 'Audio', 'Fonts', 'Graphics', '3D Models', 'Plugins', 'Themes',
                'Game Downloads', 'Digital Subscriptions',
            ],
            'Services' => [
                'Education', 'Tutoring', 'Consulting', 'Design Services',
                'Marketing Services', 'IT Services', 'Home Services', 'Repair Services',
                'Cleaning Services', 'Beauty Services', 'Fitness Services', 'Photography',
                'Event Services', 'Travel Services', 'Professional Services',
            ],
            'B2B & Business Supplies' => [
                'Office Equipment', 'Office Furniture', 'Industrial Supplies',
                'Restaurant Supplies', 'Hotel Supplies', 'Retail Supplies', 'Packaging',
                'Shipping Supplies', 'Medical Business Supplies',
                'Construction Supplies', 'Agricultural Supplies', 'Wholesale Products',
            ],
            'Agriculture' => [
                'Seeds', 'Fertilizers', 'Pesticides', 'Farming Tools',
                'Irrigation Equipment', 'Agricultural Machinery', 'Livestock Supplies',
                'Poultry Supplies', 'Animal Feed', 'Greenhouse Equipment',
                'Gardening Supplies',
            ],
            'Renewable Energy' => [
                'Solar Panels', 'Solar Inverters', 'Batteries', 'Solar Lighting',
                'Solar Accessories', 'Wind Energy Equipment', 'Portable Power Stations',
                'EV Chargers', 'Energy Storage',
            ],
            'Security & Safety' => [
                'CCTV Cameras', 'Alarm Systems', 'Access Control', 'Smart Locks',
                'Safes', 'Fire Extinguishers', 'Smoke Detectors', 'Safety Clothing',
                'Protective Equipment', 'First Aid', 'Emergency Equipment',
            ],
            'Fashion & Beauty for Specific Demographics' => [
                "Men's", "Women's", "Boys'", "Girls'", 'Babies', 'Teens', 'Seniors',
                'Maternity', 'Plus Size', 'Petite', 'Tall', 'Adaptive Clothing',
            ],
        ];
    }
}
