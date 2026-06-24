<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

/**
 * SkillSeeder - Comprehensive Industry Skills Database
 * 
 * Populates the skills table with 300+ industry-standard skills across
 * multiple categories: IT, Medical, Construction, Admin, and more.
 * 
 * Usage: php artisan db:seed --class=SkillSeeder
 */
class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if skills table already has data
        if (Skill::count() > 0) {
            $this->command->info('Skills table already has data. Skipping seeder.');
            return;
        }

        $this->command->info('Seeding 300+ industry skills...');

        // Comprehensive skills array organized by category
        $skillsByCategory = [
            'Information Technology' => [
                "3D Modeling (Blender/Maya)",
                "Accounting (GAAP/IFRS)",
                "JavaScript", "TypeScript", "React", "Vue.js", "Angular", "Node.js", "Python", "Java", "C#", "PHP",
                "Laravel", "Django", "Ruby on Rails", "Express.js", "Next.js", "GraphQL", "REST API", "SQL", "MongoDB",
                "PostgreSQL", "MySQL", "Redis", "Docker", "Kubernetes", "AWS", "Azure", "Google Cloud", "Git", "CI/CD",
                "Linux", "DevOps", "Microservices", "System Design", "Data Structures", "Algorithms",
                "Machine Learning", "Deep Learning", "TensorFlow", "PyTorch", "Data Science", "Artificial Intelligence",
                "Blockchain", "Cybersecurity", "Network Security", "Ethical Hacking", "Penetration Testing",
                "Cloud Computing", "Serverless Architecture", "API Development", "Mobile Development", "iOS Development",
                "Android Development", "React Native", "Flutter", "Unity", "Unreal Engine", "Game Development",
                "UI/UX Design", "Figma", "Adobe XD", "Sketch", "Photoshop", "Illustrator", "InDesign",
                "Agile Methodology", "Scrum", "Kanban", "Project Management", "JIRA", "Confluence",
                "Quality Assurance", "Manual Testing", "Automated Testing", "Selenium", "Cypress", "Jest",
                "Technical Writing", "Documentation", "API Documentation", "System Administration",
                "Database Administration", "Data Analysis", "Business Intelligence", "Tableau", "Power BI",
                "Web Development", "Frontend Development", "Backend Development", "Full-Stack Development",
                "Software Architecture", "Enterprise Architecture", "Solution Architecture",
                "Network Administration", "IT Support", "Help Desk", "Technical Support",
                "Cloud Security", "DevSecOps", "Infrastructure as Code", "Terraform", "Ansible",
                "Big Data", "Hadoop", "Spark", "Data Engineering", "ETL", "Data Warehousing",
                "Computer Vision", "Natural Language Processing", "Robotics", "IoT", "Edge Computing"
            ],
            
            'Medical & Healthcare' => [
                "Triage", "Patient Advocacy", "Sterilization Procedures",
                "Patient Care", "Medical Terminology", "Vital Signs Monitoring", "CPR Certification", "First Aid",
                "Medical Records Management", "HIPAA Compliance", "Medical Billing", "Medical Coding",
                "Phlebotomy", "EKG Monitoring", "Wound Care", "Medication Administration", "IV Therapy",
                "Medical Assistance", "Nursing Assistant", "Home Health Care", "Elderly Care", "Pediatric Care",
                "Medical Transcription", "Medical Imaging", "Radiology", "Ultrasound", "MRI Operation",
                "Surgical Assistance", "Operating Room Technician", "Anesthesia Technology", "Respiratory Therapy",
                "Physical Therapy", "Occupational Therapy", "Speech Therapy", "Rehabilitation",
                "Mental Health Counseling", "Psychiatric Care", "Substance Abuse Counseling", "Behavioral Health",
                "Dental Assistance", "Dental Hygiene", "Orthodontic Assistance", "Dental Surgery",
                "Optometry Assistance", "Ophthalmology Technician", "Audiology", "Hearing Aid Fitting",
                "Pharmacy Technician", "Compounding", "Medication Review", "Drug Interactions",
                "Clinical Research", "Clinical Trials", "Regulatory Affairs", "FDA Compliance",
                "Healthcare Management", "Hospital Administration", "Clinic Management", "Healthcare IT",
                "Telemedicine", "Remote Patient Monitoring", "Digital Health", "Health Informatics",
                "Epidemiology", "Public Health", "Disease Prevention", "Health Education",
                "Nutrition Counseling", "Dietary Planning", "Food Service Management", "Menu Planning",
                "Medical Laboratory Technology", "Blood Analysis", "Pathology", "Histology",
                "Emergency Medical Services", "Paramedic", "EMT", "Ambulance Services",
                "Chiropractic Care", "Acupuncture", "Massage Therapy", "Alternative Medicine"
            ],
            
            'Construction & Trades' => [
                "Scaffolding", "Blueprint Interpretation", "Site Safety Management",
                "Carpentry", "Electrical Work", "Plumbing", "HVAC Installation", "Welding", "Metal Fabrication",
                "Concrete Work", "Masonry", "Tiling", "Flooring Installation", "Roofing", "Insulation",
                "Painting", "Drywall Installation", "Framing", "Demolition", "Excavation", "Landscaping",
                "Heavy Equipment Operation", "Crane Operation", "Forklift Operation", "Bulldozer Operation",
                "Construction Management", "Project Coordination", "Site Supervision", "Quality Control",
                "Building Inspection", "Code Compliance", "Safety Inspection", "OSHA Compliance",
                "Electrical Troubleshooting", "Pipe Fitting", "Ductwork Installation", "Refrigeration",
                "Solar Panel Installation", "Green Building", "LEED Certification", "Energy Auditing",
                "Road Construction", "Bridge Construction", "Infrastructure Development", "Civil Engineering",
                "Surveying", "Site Layout", "Grade Checking", "Utility Installation",
                "Fire Protection Systems", "Sprinkler Installation", "Alarm Systems", "Security Systems",
                "Glass Installation", "Window Installation", "Door Installation", "Locksmithing",
                "Pool Construction", "Fence Installation", "Deck Building", "Patio Construction",
                "Kitchen Installation", "Bathroom Renovation", "Home Remodeling", "Interior Finishing",
                "Exterior Finishing", "Siding Installation", "Gutter Installation", "Roofing Repair",
                "Concrete Pumping", "Asphalt Paving", "Sealcoating", "Line Striping",
                "Structural Steel Erection", "Ironworking", "Rigging", "Signal Person",
                "Underwater Welding", "Commercial Diving", "Offshore Construction"
            ],
            
            'Administrative & Office' => [
                "Zoom/Google Meet Proficiency", "Remote Work Ethics",
                "Microsoft Office", "Excel", "Word", "PowerPoint", "Outlook", "Access", "OneNote",
                "Google Workspace", "Google Docs", "Google Sheets", "Google Slides", "Google Drive",
                "Data Entry", "Typing", "Transcription", "Document Management", "File Organization",
                "Calendar Management", "Appointment Scheduling", "Travel Coordination", "Event Planning",
                "Customer Service", "Client Relations", "Phone Etiquette", "Email Communication",
                "Bookkeeping", "Accounting Software", "QuickBooks", "Xero", "Sage", "FreshBooks",
                "Payroll Processing", "Invoice Management", "Budget Tracking", "Financial Reporting",
                "Human Resources", "Recruitment", "Employee Onboarding", "Performance Management",
                "Office Management", "Facilities Management", "Supply Chain Management", "Inventory Control",
                "Project Coordination", "Task Management", "Time Management", "Deadline Management",
                "Report Writing", "Business Writing", "Technical Writing", "Grant Writing",
                "Research Skills", "Market Research", "Competitive Analysis", "Data Analysis",
                "Presentation Skills", "Public Speaking", "Meeting Facilitation", "Training",
                "Database Management", "CRM Software", "Salesforce", "HubSpot", "Zoho CRM",
                "Social Media Management", "Content Creation", "Digital Marketing", "Email Marketing",
                "SEO/SEM", "Google Analytics", "Facebook Ads", "LinkedIn Marketing", "Twitter Marketing",
                "Graphic Design", "Video Editing", "Audio Production", "Podcast Production",
                "Website Management", "WordPress", "Shopify", "E-commerce Management",
                "Legal Assistance", "Paralegal Services", "Contract Management", "Compliance",
                "Notary Services", "Document Preparation", "Legal Research", "Court Filing",
                "Medical Administration", "Medical Billing", "Medical Scheduling", "Health Records",
                "Education Administration", "Student Records", "Academic Advising", "Curriculum Development"
            ],
            
            'Domestic & Household' => [
                "Cooking", "Cleaning", "Laundry", "Ironing", "Childcare", "Elderly Care", "Pet Care", "Gardening",
                "House Management", "Meal Preparation", "Baby Sitting", "First Aid", "Driving", "Shopping",
                "Arabic Cuisine", "Asian Cuisine", "Western Cuisine", "Indian Cuisine", "Filipino Cuisine",
                "Chinese Cuisine", "Mediterranean Cuisine", "Italian Cuisine", "Mexican Cuisine", "Thai Cuisine",
                "Baking", "Pastry Making", "Cake Decorating", "Food Presentation", "Menu Planning",
                "Housekeeping", "Deep Cleaning", "Organizational Skills", "Decluttering", "Space Management",
                "Child Development", "Early Childhood Education", "Tutoring", "Homework Assistance",
                "Special Needs Care", "Disability Care", "Memory Care", "Palliative Care",
                "Pet Grooming", "Dog Walking", "Pet Training", "Aquarium Care", "Exotic Pet Care",
                "Landscaping", "Lawn Care", "Tree Trimming", "Irrigation Systems", "Pool Maintenance",
                "Home Maintenance", "Minor Repairs", "Painting", "Basic Plumbing", "Basic Electrical",
                "Sewing", "Mending", "Alterations", "Embroidery", "Knitting", "Crocheting",
                "Household Budgeting", "Grocery Shopping", "Meal Planning", "Food Storage",
                "Laundry Care", "Stain Removal", "Fabric Care", "Wardrobe Organization",
                "Home Security", "Surveillance Systems", "Alarm Systems", "Access Control",
                "Pest Control", "Insect Control", "Rodent Control", "Termite Treatment",
                "Waste Management", "Recycling", "Composting", "Disposal Systems",
                "Home Entertainment Systems", "Smart Home Technology", "Home Automation",
                "Furniture Assembly", "Moving Assistance", "Packing", "Unpacking",
                "Event Hosting", "Party Planning", "Guest Services", "Hospitality"
            ],
            
            'Transportation & Logistics' => [
                "Light Vehicle", "Heavy Vehicle", "Motorcycle", "Bus Driving", "Truck Driving", "Forklift",
                "Defensive Driving", "GPS Navigation", "Vehicle Maintenance", "Clean Driving Record",
                "UAE License", "GCC License", "International License",
                "Delivery Services", "Courier Services", "Package Handling", "Route Planning",
                "Logistics Coordination", "Supply Chain Management", "Inventory Management",
                "Warehouse Operations", "Order Picking", "Packing", "Shipping",
                "Fleet Management", "Vehicle Tracking", "Fuel Management", "Maintenance Scheduling",
                "Air Transportation", "Aviation", "Flight Operations", "Ground Support",
                "Maritime Transportation", "Shipping Operations", "Port Operations", "Customs Clearance",
                "Rail Transportation", "Train Operations", "Railway Maintenance", "Signal Operations",
                "Public Transportation", "Bus Operations", "Metro Systems", "Taxi Services",
                "Ride Sharing", "Uber", "Careem", "Delivery Apps", "Route Optimization",
                "Traffic Management", "Parking Management", "Valet Services", "Car Rental",
                "Vehicle Inspection", "Safety Compliance", "Emissions Testing", "Vehicle Registration",
                "Towing Services", "Roadside Assistance", "Breakdown Services", "Recovery Operations",
                "Freight Forwarding", "Customs Brokerage", "Import/Export Documentation",
                "Cold Chain Logistics", "Refrigerated Transport", "Temperature Control",
                "Hazardous Materials Handling", "Dangerous Goods Transportation", "Safety Protocols",
                "Heavy Machinery Transport", "Oversized Load Transport", "Specialized Transport"
            ],
            
            'Languages & Communication' => [
                "English", "Arabic", "Hindi", "Urdu", "Tagalog", "Bengali", "French", "Spanish", "Chinese",
                "Malayalam", "Tamil", "Nepali", "Sinhala", "Indonesian",
                "Japanese", "Korean", "German", "Italian", "Portuguese", "Russian", "Turkish",
                "Persian", "Dutch", "Swedish", "Norwegian", "Danish", "Finnish", "Polish",
                "Czech", "Hungarian", "Romanian", "Bulgarian", "Serbian", "Croatian", "Greek",
                "Hebrew", "Thai", "Vietnamese", "Khmer", "Burmese", "Lao", "Malay",
                "Swahili", "Amharic", "Somali", "Zulu", "Afrikaans", "Yoruba", "Igbo",
                "Sign Language", "Braille", "Morse Code",
                "Interpretation", "Translation", "Localization", "Transcription",
                "Public Speaking", "Presentation Skills", "Debate", "Negotiation", "Mediation",
                "Business Communication", "Technical Writing", "Creative Writing", "Copywriting",
                "Content Writing", "Blog Writing", "Social Media Writing", "Email Marketing",
                "Press Release Writing", "Grant Writing", "Proposal Writing", "Report Writing",
                "Speech Writing", "Script Writing", "Screenwriting", "Playwriting",
                "Editing", "Proofreading", "Fact-Checking", "Research Skills",
                "Cross-Cultural Communication", "Intercultural Competence", "Diversity Training",
                "Customer Communication", "Client Relations", "Stakeholder Management",
                "Media Relations", "Public Relations", "Crisis Communication", "Brand Communication"
            ],
            
            'General Professional Skills' => [
                "Communication", "Leadership", "Team Management", "Problem Solving", "Critical Thinking",
                "Time Management", "Project Management", "Customer Service", "Sales", "Marketing",
                "Negotiation", "Presentation", "Public Speaking", "Data Analysis", "Report Writing", "Budget Management",
                "Strategic Planning", "Business Development", "Partnership Development", "Networking",
                "Decision Making", "Risk Management", "Change Management", "Conflict Resolution",
                "Mentoring", "Coaching", "Training", "Teaching", "Facilitation", "Motivation",
                "Innovation", "Creativity", "Design Thinking", "Brainstorming", "Idea Generation",
                "Analytical Skills", "Research Skills", "Data Visualization", "Statistical Analysis",
                "Quality Control", "Process Improvement", "Efficiency Optimization", "Workflow Design",
                "Compliance", "Regulatory Knowledge", "Policy Development", "Procedure Writing",
                "Entrepreneurship", "Business Planning", "Financial Management", "Investment Analysis",
                "E-commerce", "Digital Transformation", "Technology Adoption", "System Integration",
                "Vendor Management", "Contract Management", "Procurement", "Purchasing",
                "Facilitation", "Workshop Leadership", "Event Management", "Conference Planning",
                "Brand Management", "Product Management", "Service Management", "Operations Management"
            ]
        ];

        // Insert skills by category
        $totalSkills = 0;
        foreach ($skillsByCategory as $category => $skills) {
            $this->command->info("Seeding {$category}: " . count($skills) . ' skills');
            
            foreach ($skills as $skillName) {
                // Use firstOrCreate to handle duplicates gracefully
                $skill = Skill::firstOrCreate([
                    'name' => trim($skillName),
                ], [
                    'category' => $category,
                    'is_active' => true,
                    'usage_count' => 0
                ]);
                
                if ($skill->wasRecentlyCreated) {
                    $totalSkills++;
                }
            }
        }

        // Add some additional missing skills that weren't categorized
        $additionalSkills = [
            "Underwater Welding",
            "Space Technology",
            "Renewable Energy",
            "Sustainable Development",
            "Carbon Footprint Analysis",
            "Environmental Compliance",
            "Green Building Certification",
            "Energy Auditing",
            "Waste Management",
            "Recycling Programs",
            "Water Conservation",
            "Air Quality Management",
            "Noise Pollution Control",
            "Soil Testing",
            "Hazardous Material Handling",
            "Emergency Response",
            "Disaster Management",
            "Crisis Management",
            "Business Continuity Planning",
            "Risk Assessment",
            "Safety Management",
            "Occupational Health",
            "Industrial Hygiene",
            "Ergonomics",
            "Workplace Safety",
            "Fire Safety",
            "Electrical Safety",
            "Chemical Safety",
            "Biological Safety",
            "Radiation Safety",
            "Construction Safety",
            "Mining Safety",
            "Oil & Gas Safety",
            "Marine Safety",
            "Aviation Safety",
            "Rail Safety",
            "Transport Safety",
            "Food Safety",
            "Product Safety",
            "Consumer Protection",
            "Quality Assurance",
            "Standards Compliance",
            "ISO Certification",
            "Audit Management",
            "Internal Controls",
            "Fraud Detection",
            "Forensic Accounting",
            "Legal Compliance",
            "Contract Law",
            "Employment Law",
            "Intellectual Property",
            "Data Privacy",
            "Cyber Law",
            "International Law",
            "Trade Law",
            "Tax Law",
            "Corporate Law",
            "Securities Law",
            "Banking Law",
            "Insurance Law",
            "Real Estate Law",
            "Environmental Law",
            "Health Law",
            "Education Law",
            "Immigration Law"
        ];

        $this->command->info('Seeding additional professional skills: ' . count($additionalSkills) . ' skills');
        
        foreach ($additionalSkills as $skillName) {
            // Use firstOrCreate to handle duplicates gracefully
            $skill = Skill::firstOrCreate([
                'name' => trim($skillName),
            ], [
                'category' => 'Professional & Legal',
                'is_active' => true,
                'usage_count' => 0
            ]);
            
            if ($skill->wasRecentlyCreated) {
                $totalSkills++;
            }
        }

        $this->command->info("Successfully seeded {$totalSkills} skills into the database!");
        
        // Show category breakdown
        $categoryCounts = Skill::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
            
        $this->command->info("\nSkills by category:");
        foreach ($categoryCounts as $category) {
            $this->command->line("  {$category->category}: {$category->count} skills");
        }
    }
}
