<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Industry;
use Illuminate\Support\Facades\DB;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if industries table is already populated
        if (Industry::count() > 0) {
            $this->command->info('Industries table already seeded. Skipping...');
            return;
        }

        $industries = [
            // Primary Sectors
            "Agriculture", "Forestry", "Fishing", "Mining", "Oil & Gas", 
            "Construction", "Real Estate", "Architecture", "Interior Design",
            
            // Manufacturing & Transport
            "Automobile", "Aerospace", "Transportation", "Logistics", "Shipping", 
            "Warehousing", "Railways", "Airlines", "Manufacturing", "Textiles", 
            "Garments", "Leather", "Footwear", "Furniture", "Paper", "Plastic", 
            "Steel", "Cement", "Glass", "Chemicals", "Petrochemicals", "Metallurgy",
            
            // Services
            "Hospitality", "Hotels", "Restaurants", "Food & Beverage", "Catering", 
            "Travel", "Tourism", "Event Management", "Entertainment", "Film", 
            "Television", "Music", "Sports", "Fitness", "Gaming", "Gambling & Casinos",
            
            // Health & Science
            "Healthcare", "Hospitals", "Pharmaceuticals", "Medical Devices", 
            "Biotechnology", "Genetics", "Clinical Research", "Veterinary",
            
            // Education
            "Education", "Schools", "Universities", "Training Institutes", "E-Learning",
            
            // Tech & IT
            "IT Services", "Software Development", "Hardware Manufacturing", 
            "Cybersecurity", "E-Commerce", "Artificial Intelligence", "Machine Learning", 
            "Blockchain", "Cloud Computing", "Robotics", "Automation", "IoT", 
            "Big Data", "Data Science", "Telecommunications", "Internet Services",
            
            // Finance & Legal
            "Banking", "Finance", "Insurance", "Investment", "Microfinance", 
            "Fintech", "Accounting", "Audit", "Taxation", "Legal", "Consulting", 
            "Venture Capital", "Private Equity", "Stock Exchange", "Wealth Management",
            
            // Public Sector & Non-Profit
            "Government", "Public Services", "Defence", "Army", "Navy", "Air Force", 
            "Police", "Fire & Rescue", "Civil Defence", "NGOs", "Charities", 
            "International Relations", "Policy Making", "Social Work",
            
            // Energy & Environment
            "Power", "Energy", "Renewables", "Solar", "Wind Energy", "Water Management", 
            "Sanitation", "Recycling", "Environmental Services", "Sustainability",
            
            // Retail & Trade
            "Retail", "Wholesale", "Import/Export", "Merchandising", "Distribution", 
            "Consumer Goods", "Luxury Goods", "Fashion", "Cosmetics", "Jewellery",
            
            // Specialized
            "Human Resources", "Recruitment", "BPO", "KPO", "Call Centres", 
            "Outsourcing", "Facility Management", "Security Services", "Courier Services",
            "Ride-Hailing", "Food Delivery", "Domestic Work", "Childcare", "Elderly Care",
            "Cleaning Services", "Landscaping", "Gardening",
            
            // Additional Modern Sectors
            "EdTech", "HealthTech", "Space Exploration", "Personal Care", "Wellness & Spa",
            "Consumer Electronics", "Industrial Automation"
        ];

        $this->command->info('Seeding industries...');
        
        DB::transaction(function () use ($industries) {
            foreach ($industries as $name) {
                Industry::firstOrCreate([
                    'name' => trim($name),
                ]);
            }
        });

        $this->command->info(count($industries) . ' industries seeded successfully!');
    }
}
