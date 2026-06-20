-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2023 at 08:49 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pglife`
--

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE `amenities` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(150) NOT NULL,
  `icon` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES
(1, 'Wifi', 'Common Area', 'wifi'),
(2, 'Power Backup', 'Building', 'powerbackup'),
(3, 'Fire Extinguisher', 'Building', 'fireext'),
(4, 'TV', 'Common Area', 'tv'),
(5, 'Bed with Mattress', 'Bedroom', 'bed'),
(6, 'Parking', 'Building', 'parking'),
(7, 'Water Purifier', 'Common Area', 'rowater'),
(8, 'Dining', 'Common Area', 'dining'),
(9, 'Air Conditioner', 'Bedroom', 'ac'),
(10, 'Washing Machine', 'Common Area', 'washingmachine'),
(11, 'Lift', 'Building', 'lift'),
(12, 'CCTV', 'Building', 'cctv'),
(13, 'Geyser', 'Washroom', 'geyser');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`) VALUES
(1, 'Delhi'),
(2, 'Mumbai'),
(3, 'Bengaluru'),
(4, 'Hyderabad'),
(5, 'Kolkata'),
(6, 'Chennai'),
(7, 'Pune'),
(8, 'Ahmedabad'),
(9, 'Jaipur'),
(10, 'Noida'),
(11, 'Gurgaon');

-- --------------------------------------------------------

--
-- Table structure for table `interested_users_properties`
--

CREATE TABLE `interested_users_properties` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `interested_users_properties`
--

INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 5),
(4, 2, 1),
(5, 2, 5),
(6, 3, 1),
(7, 3, 2),
(8, 3, 5);

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `gender` enum('male','female','unisex') NOT NULL,
  `rent` int(11) NOT NULL,
  `rating_clean` float(2,1) NOT NULL,
  `rating_food` float(2,1) NOT NULL,
  `rating_safety` float(2,1) NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `city_id`, `owner_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`, `views`) VALUES
(1, 1, NULL, 'Saxena\'s Paying Guest', 'H.No. 3958 Kaseru Walan, Pahar Ganj, New Delhi, Delhi 110055', 'Furnished studio apartment - share it with close friends! Located in posh area of Bijwasan in Delhi, this house is available for both boys and girls. Go for a private room or opt for a shared one and make it your own abode. Go out with your new friends - catch a movie at the nearest cinema hall or just chill in a cafe which is not even 2 kms away. Unwind with your flatmates after a long day at work/college. With a common living area and a shared kitchen, make your own FRIENDS moments. After all, there\'s always a Joey with unlimited supply of food. Remember, all it needs is one crazy story to convert a roomie into a BFF. What\'s nearby/Your New Neighborhood 4.0 Kms from Dwarka Sector- 21 Metro Station.', 'male', 5000, 4.3, 3.4, 4.8, 12),
(2, 1, NULL, 'Navrang PG Home', '644-C,Mohalla Baoli 6 Tooti Chowk, Paharganj, New Delhi, Delhi 110055', 'Furnished studio apartment - share it with close friends! Located in posh area of Bijwasan in Delhi, this house is available for both boys and girls. Go for a private room or opt for a shared one and make it your own abode. Go out with your new friends - catch a movie at the nearest cinema hall or just chill in a cafe which is not even 2 kms away. Unwind with your flatmates after a long day at work/college. With a common living area and a shared kitchen, make your own FRIENDS moments. After all, there\'s always a Joey with unlimited supply of food. Remember, all it needs is one crazy story to convert a roomie into a BFF. What\'s nearby/Your New Neighborhood 4.0 Kms from Dwarka Sector- 21 Metro Station.', 'unisex', 6000, 2.9, 3.4, 3.8, 8),
(3, 2, NULL, 'Navkar Paying Guest', '44, Juhu Scheme, Juhu, Mumbai, Maharashtra 400058', 'Furnished studio apartment - share it with close friends! Located in posh area of Bijwasan in Delhi, this house is available for both boys and girls. Go for a private room or opt for a shared one and make it your own abode. Go out with your new friends - catch a movie at the nearest cinema hall or just chill in a cafe which is not even 2 kms away. Unwind with your flatmates after a long day at work/college. With a common living area and a shared kitchen, make your own FRIENDS moments. After all, there\'s always a Joey with unlimited supply of food. Remember, all it needs is one crazy story to convert a roomie into a BFF. What\'s nearby/Your New Neighborhood 4.0 Kms from Dwarka Sector- 21 Metro Station.', 'female', 9500, 3.9, 3.8, 4.9, 23),
(4, 2, NULL, 'PG for Girls Borivali West', 'Plot no.258/D4, Gorai no.2, Borivali West, Mumbai, Maharashtra 400092', 'Furnished studio apartment - share it with close friends! Located in posh area of Bijwasan in Delhi, this house is available for both boys and girls. Go for a private room or opt for a shared one and make it your own abode. Go out with your new friends - catch a movie at the nearest cinema hall or just chill in a cafe which is not even 2 kms away. Unwind with your flatmates after a long day at work/college. With a common living area and a shared kitchen, make your own FRIENDS moments. After all, there\'s always a Joey with unlimited supply of food. Remember, all it needs is one crazy story to convert a roomie into a BFF. What\'s nearby/Your New Neighborhood 4.0 Kms from Dwarka Sector- 21 Metro Station.', 'female', 8000, 4.2, 4.1, 4.5, 17),
(5, 2, NULL, 'Ganpati Paying Guest', 'Police Beat, Sainath Complex, Besides, SV Rd, Daulat Nagar, Borivali East, Mumbai - 400066', 'Furnished studio apartment - share it with close friends! Located in posh area of Bijwasan in Delhi, this house is available for both boys and girls. Go for a private room or opt for a shared one and make it your own abode. Go out with your new friends - catch a movie at the nearest cinema hall or just chill in a cafe which is not even 2 kms away. Unwind with your flatmates after a long day at work/college. With a common living area and a shared kitchen, make your own FRIENDS moments. After all, there\'s always a Joey with unlimited supply of food. Remember, all it needs is one crazy story to convert a roomie into a BFF. What\'s nearby/Your New Neighborhood 4.0 Kms from Dwarka Sector- 21 Metro Station.', 'male', 8500, 4.2, 3.9, 4.6, 9),
(6, 5, NULL, 'Salt Lake Premium PG', 'Block GD, Sector III, Salt Lake, Kolkata, West Bengal 700097', 'A premium, fully furnished studio PG located in the commercial heart of Salt Lake. Features high-speed internet, regular room cleaning, and high-quality homestyle meals. Great for IT professionals.', 'unisex', 7500, 4.5, 4.2, 4.7, 0),
(7, 5, NULL, 'South City Residency', 'Prince Anwar Shah Road, Lake Gardens, Kolkata, West Bengal 700068', 'Excellent ladies PG with 24/7 security, power backup, and geyser. Close to malls and transport hubs. Unmatched safety features.', 'female', 8000, 4.8, 4.0, 4.9, 0),
(8, 6, NULL, 'OMR IT Corridor PG', 'Rajiv Gandhi Salai, Thoraipakkam, Chennai, Tamil Nadu 600097', 'Convenient PG for software engineers working on OMR. Rent includes high-speed WiFi, AC, laundry, and delicious food.', 'male', 7000, 4.2, 3.8, 4.5, 0),
(9, 6, NULL, 'Adyar Girls Abode', 'Kasturibai Nagar, Adyar, Chennai, Tamil Nadu 600020', 'Fully furnished twin sharing rooms for women. Walkable distance to major colleges. High cleanliness standards and round-the-clock security.', 'female', 9000, 4.6, 4.2, 4.8, 0),
(10, 7, NULL, 'Hinjewadi Executive PG', 'Phase 1, Hinjewadi, Pune, Maharashtra 411057', 'Spacious executive rooms for guys. High-speed WiFi, power backup, and modern dining facility. Unwind after work at the common lounge.', 'male', 6500, 4.1, 3.9, 4.6, 0),
(11, 7, NULL, 'Viman Nagar Unisex PG', 'Near Datta Mandir, Viman Nagar, Pune, Maharashtra 411014', 'Premium co-living PG with private and shared options. Very close to IT parks and symbiosis college. Comes with Gym and common area access.', 'unisex', 9000, 4.4, 4.3, 4.5, 0),
(12, 8, NULL, 'Satellite Luxury PG', 'Opp. Star Bazaar, Satellite, Ahmedabad, Gujarat 380015', 'Luxurious single/double sharing rooms for students and professionals. Equipped with AC, TV, Fridge, and daily housekeeping.', 'unisex', 8500, 4.7, 4.4, 4.6, 0),
(13, 8, NULL, 'Vastrapur Boys Hostel', 'Near Vastrapur Lake, Vastrapur, Ahmedabad, Gujarat 380015', 'Affordable boys PG with all amenities. Walking distance to major colleges. Rent covers WiFi, water purifier, and housekeeping.', 'male', 5500, 3.9, 4.0, 4.2, 0),
(14, 9, NULL, 'Malviya Nagar PG', 'Sector 3, Malviya Nagar, Jaipur, Rajasthan 302017', 'Perfect PG for professionals and students. Neat rooms with bed, mattress, wardrobe, and desk. Home cooked meals provided daily.', 'unisex', 6000, 4.3, 4.1, 4.5, 0),
(15, 9, NULL, 'C-Scheme Executive Girls PG', 'Bhagwan Das Road, C-Scheme, Jaipur, Rajasthan 302001', 'High-end women PG in the most upscale locality. Very safe and secure, comes with high-speed internet, AC, geyser, and delicious vegetarian meals.', 'female', 9500, 4.9, 4.6, 4.9, 0),
(16, 10, NULL, 'Noida Sector 62 Co-living', 'Block B, Sector 62, Noida, Uttar Pradesh 201301', 'Modern co-living space with high-speed internet, power backup, and smart TV. Perfect for metro commuters and office goers.', 'unisex', 7500, 4.2, 3.8, 4.7, 0),
(17, 10, NULL, 'Sector 15 Ladies PG', 'Naya Bans, Sector 15, Noida, Uttar Pradesh 201301', 'Cozy ladies PG with AC, TV, and geyser. Homestyle food and 24/7 security. Just 2 minutes walk from Metro Station.', 'female', 8500, 4.5, 4.0, 4.8, 0),
(18, 11, NULL, 'DLF Phase 3 Boys PG', 'U Block, DLF Phase 3, Gurgaon, Haryana 122002', 'Fully furnished boys PG with all amenities. Rent includes WiFi, AC, laundry, and meals. 5 minutes from Cyber City.', 'male', 8000, 4.3, 3.9, 4.6, 0),
(19, 11, NULL, 'Sushant Lok Girls Nest', 'Sector 43, Sushant Lok Phase 1, Gurgaon, Haryana 122002', 'Luxurious women PG with private rooms, high safety, power backup, AC, and daily housekeeping. Walking distance to HUDA City Center.', 'female', 11000, 4.7, 4.3, 4.9, 0),
(20, 7, NULL, 'Pune Elite Co-living', 'Kalyani Nagar, Pune, Maharashtra 411006', 'Premium co-living space in one of Pune's top localities. Fully furnished with AC, high-speed internet, home-cooked food, and daily housekeeping.', 'unisex', 8500, 4.5, 4.4, 4.6, 0),
(21, 3, NULL, 'Indiranagar Tech Hub PG', 'CMH Road, 1st Main, Indiranagar, Bengaluru, Karnataka 560038', 'Premium PG for tech professionals near major IT parks. Fully furnished with AC rooms, high-speed WiFi, and delicious South Indian food. Walking distance to metro station.', 'male', 8500, 4.4, 4.2, 4.7, 0),
(22, 3, NULL, 'Koramangala Girls Nest', '4th Block, Koramangala, Bengaluru, Karnataka 560034', 'Modern fully-furnished PG for women in the heart of Bengaluru. Located close to top colleges and IT companies. Features AC, Wifi, power backup, and 24/7 security.', 'female', 10000, 4.6, 4.3, 4.9, 0),
(23, 4, NULL, 'Hitec City Executive PG', 'Madhapur Main Road, Hitec City, Hyderabad, Telangana 500081', 'Best-in-class PG for software professionals near Hitec City. Offers AC rooms, high-speed internet, power backup, and daily meals. Close to Cyber Towers.', 'male', 8000, 4.3, 4.0, 4.6, 0),
(24, 4, NULL, 'Banjara Hills Premium Ladies PG', 'Road No. 12, Banjara Hills, Hyderabad, Telangana 500034', 'Luxurious PG exclusively for women in premium locality. Features gated security, AC rooms, CCTV, geyser, and excellent food. Very close to top hospitals and malls.', 'female', 9500, 4.7, 4.4, 4.9, 0);

-- --------------------------------------------------------

--
-- Table structure for table `properties_amenities`
--

CREATE TABLE `properties_amenities` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `properties_amenities`
--

INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 4),
(4, 1, 5),
(5, 1, 7),
(6, 1, 8),
(7, 1, 9),
(8, 1, 10),
(9, 1, 11),
(10, 1, 13),
(11, 2, 1),
(12, 2, 2),
(13, 2, 3),
(14, 2, 4),
(15, 2, 5),
(16, 2, 7),
(17, 2, 8),
(18, 2, 9),
(19, 2, 10),
(20, 2, 11),
(21, 2, 13),
(22, 3, 1),
(23, 3, 2),
(24, 3, 3),
(25, 3, 4),
(26, 3, 5),
(27, 3, 7),
(28, 3, 8),
(29, 3, 10),
(30, 3, 11),
(31, 3, 12),
(32, 3, 13),
(33, 4, 1),
(34, 4, 3),
(35, 4, 4),
(36, 4, 5),
(37, 4, 7),
(38, 4, 8),
(39, 4, 10),
(40, 4, 11),
(41, 4, 12),
(42, 4, 13),
(43, 5, 1),
(44, 5, 3),
(45, 5, 4),
(46, 5, 5),
(47, 5, 7),
(48, 5, 8),
(49, 5, 10),
(50, 5, 11),
(51, 5, 12),
(52, 5, 13),
(53, 6, 1), (54, 6, 2), (55, 6, 4), (56, 6, 5), (57, 6, 7), (58, 6, 8), (59, 6, 9), (60, 6, 10), (61, 6, 11), (62, 6, 12), (63, 6, 13),
(64, 7, 1), (65, 7, 2), (66, 7, 4), (67, 7, 5), (68, 7, 7), (69, 7, 9), (70, 7, 10), (71, 7, 11), (72, 7, 12), (73, 7, 13),
(74, 8, 1), (75, 8, 4), (76, 8, 5), (77, 8, 9), (78, 8, 10), (79, 8, 12), (80, 8, 13),
(81, 9, 1), (82, 9, 2), (83, 9, 5), (84, 9, 7), (85, 9, 10), (86, 9, 12), (87, 9, 13),
(88, 10, 1), (89, 10, 2), (90, 10, 4), (91, 10, 5), (92, 10, 8), (93, 10, 12),
(94, 11, 1), (95, 11, 2), (96, 11, 4), (97, 11, 5), (98, 11, 7), (99, 11, 8), (100, 11, 9), (101, 11, 10), (102, 11, 11), (103, 11, 12), (104, 11, 13),
(105, 12, 1), (106, 12, 4), (107, 12, 5), (108, 12, 7), (109, 12, 9), (110, 12, 10), (111, 12, 12), (112, 12, 13),
(113, 13, 1), (114, 13, 5), (115, 13, 7), (116, 13, 12), (117, 13, 13),
(118, 14, 1), (119, 14, 2), (120, 14, 5), (121, 14, 7), (122, 14, 8), (123, 14, 12),
(124, 15, 1), (125, 15, 2), (126, 15, 5), (127, 15, 7), (128, 15, 9), (129, 15, 10), (130, 15, 12), (131, 15, 13),
(132, 16, 1), (133, 16, 2), (134, 16, 4), (135, 16, 5), (136, 16, 7), (137, 16, 9), (138, 16, 10), (139, 16, 12),
(140, 17, 1), (141, 17, 4), (142, 17, 5), (143, 17, 7), (144, 17, 9), (145, 17, 10), (146, 17, 12), (147, 17, 13),
(148, 18, 1), (149, 18, 2), (150, 18, 4), (151, 18, 5), (152, 18, 9), (153, 18, 10), (154, 18, 12), (155, 18, 13),
(156, 19, 1), (157, 19, 2), (158, 19, 4), (159, 19, 5), (160, 19, 7), (161, 19, 9), (162, 19, 10), (163, 19, 12), (164, 19, 13),
(165, 20, 1), (166, 20, 2), (167, 20, 4), (168, 20, 5), (169, 20, 7), (170, 20, 8), (171, 20, 9), (172, 20, 10), (173, 20, 11), (174, 20, 12), (175, 20, 13),
(176, 21, 1), (177, 21, 2), (178, 21, 4), (179, 21, 5), (180, 21, 7), (181, 21, 9), (182, 21, 10), (183, 21, 11), (184, 21, 12), (185, 21, 13),
(186, 22, 1), (187, 22, 2), (188, 22, 5), (189, 22, 7), (190, 22, 9), (191, 22, 10), (192, 22, 11), (193, 22, 12), (194, 22, 13),
(195, 23, 1), (196, 23, 2), (197, 23, 4), (198, 23, 5), (199, 23, 9), (200, 23, 10), (201, 23, 11), (202, 23, 12), (203, 23, 13),
(204, 24, 1), (205, 24, 2), (206, 24, 5), (207, 24, 7), (208, 24, 9), (209, 24, 10), (210, 24, 11), (211, 24, 12), (212, 24, 13);

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_name` varchar(150) NOT NULL,
  `content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES
(1, 1, 'Aarav Sharma', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(2, 1, 'Karan Mehta', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(3, 2, 'Zoya Khan', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(4, 2, 'Farhan K.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(5, 2, 'Anurag S.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(6, 3, 'Mira Kapoor', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(7, 3, 'Meghna G.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(8, 4, 'Farah S.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(9, 5, 'Rajkumar P.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.'),
(10, 5, 'Sanjay B.', 'You just have to arrive at the place, it\'s fully furnished and stocked with all basic amenities and services and even your friends are welcome.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(150) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `institution_or_organization` varchar(255) NOT NULL,
  `role` enum('seeker','owner') NOT NULL DEFAULT 'seeker'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `gender`, `institution_or_organization`, `role`) VALUES
(1, 'keshab@example.com', '$2y$10$XuUwDZjh34G/xZGvWJ0bCOVniAu1OH4huL/KpggKvIZyq90k9GJRK', 'keshabkjha', '9876543210', 'male', 'Organization A', 'seeker'),
(2, 'ritesh@example.com', '$2y$10$XuUwDZjh34G/xZGvWJ0bCOVniAu1OH4huL/KpggKvIZyq90k9GJRK', 'Ritesh thakur', '9876543211', 'male', 'Organization B', 'owner'),
(3, 'puneet@example.com', '$2y$10$XuUwDZjh34G/xZGvWJ0bCOVniAu1OH4huL/KpggKvIZyq90k9GJRK', 'Puneet pandey', '9876543212', 'male', 'Organization C', 'seeker');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interested_users_properties`
--
ALTER TABLE `interested_users_properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `properties_amenities`
--
ALTER TABLE `properties_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `interested_users_properties`
--
ALTER TABLE `interested_users_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `properties_amenities`
--
ALTER TABLE `properties_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `interested_users_properties`
--
ALTER TABLE `interested_users_properties`
  ADD CONSTRAINT `interested_users_properties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `interested_users_properties_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`);

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  ADD CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `properties_amenities`
--
ALTER TABLE `properties_amenities`
  ADD CONSTRAINT `properties_amenities_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  ADD CONSTRAINT `properties_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`);

--
-- Constraints for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`);

--
-- Table structure for table `bookings`
--
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  UNIQUE KEY `unique_user_property` (`user_id`, `property_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `reviews`
--
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_name` varchar(150) NOT NULL,
  `rating` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reviews`
--
INSERT INTO `reviews` (`id`, `property_id`, `user_name`, `rating`, `content`) VALUES
(1, 1, 'John Doe', 5, 'Absolutely amazing PG! The wifi is super fast and clean rooms.'),
(2, 1, 'Alice Smith', 4, 'Very friendly owner and great food quality. Recommended!'),
(3, 2, 'Bob Johnson', 3, 'Average place. Cleanliness could be better but rent is low.');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
