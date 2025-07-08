<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sheet = $this->convertCsv(__DIR__.'..\Data\Questionssheet.csv');
        var_dump($sheet) ;
        
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }

    private function convertCsv(string $filepath){
        $data = [];

        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }

        return $data;
    }
}
