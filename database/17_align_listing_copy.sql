-- Align listing copy with the gender filter and the city the PG is actually in.
UPDATE properties p
INNER JOIN cities c ON c.id = p.city_id
SET p.description = CONCAT(
    'Furnished rooms with a shared kitchen and a common living area in ', c.name, '. ',
    CASE p.gender
        WHEN 'male' THEN 'This PG is for boys only.'
        WHEN 'female' THEN 'This PG is for girls only.'
        ELSE 'This PG is open to both boys and girls.'
    END,
    ' Choose a private room or a shared room. Cafes and local transit are a short ride away.'
);

-- Give each seeded testimonial its own sentence so the section does not repeat one line.
UPDATE testimonials SET content = 'The room was ready when I arrived, and the common kitchen is easy to share.' WHERE id = 1;
UPDATE testimonials SET content = 'Wi-Fi held up during evening classes, and the owner answers calls the same day.' WHERE id = 2;
UPDATE testimonials SET content = 'Safe street, and both men and women were already staying here when I visited.' WHERE id = 3;
UPDATE testimonials SET content = 'Shared rooms are basic but clean. Rent matched what was listed.' WHERE id = 4;
UPDATE testimonials SET content = 'I liked the living area. Friends can visit during the hours posted at the desk.' WHERE id = 5;
UPDATE testimonials SET content = 'The girls-only house felt secure, and the bathroom was cleaned every morning.' WHERE id = 6;
UPDATE testimonials SET content = 'Close to the station. I would have liked a stronger water heater in winter.' WHERE id = 7;
UPDATE testimonials SET content = 'Quiet after 10 pm. The caretaker is strict about visitors, which I appreciated.' WHERE id = 8;
UPDATE testimonials SET content = 'Food is simple and on time. The boys floor is separate from the common lounge.' WHERE id = 9;
UPDATE testimonials SET content = 'Good first month. The bed and cupboard were already in the room.' WHERE id = 10;
