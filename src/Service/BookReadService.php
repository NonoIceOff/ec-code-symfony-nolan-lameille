<?php

namespace App\Service;

use App\Repository\BookReadRepository;

class BookReadService
{
    private BookReadRepository $bookReadRepository;

    public function __construct(BookReadRepository $bookReadRepository)
    {
        $this->bookReadRepository = $bookReadRepository;
    }

    public function getAllBooksReadWithAverage(): array
    {
        $allBooksReadRaw = $this->bookReadRepository->findAll();
        $allBooksRead = [];

        foreach ($allBooksReadRaw as $bookRead) {
            $bookId = $bookRead->getBook()->getId();
            $bookName = $bookRead->getBook()->getName();
            $bookDescription = $bookRead->getBook()->getDescription();
            $rating = $bookRead->getRating();

            if (!isset($allBooksRead[$bookId])) {
                $allBooksRead[$bookId] = [
                    'book_id' => $bookId,
                    'book_name' => $bookName,
                    'book_description' => $bookDescription,
                    'total_rating' => 0,
                    'rating_count' => 0,
                    'average_rating' => 0,
                ];
            }

            $allBooksRead[$bookId]['total_rating'] += $rating;
            $allBooksRead[$bookId]['rating_count']++;
            $allBooksRead[$bookId]['average_rating'] = $allBooksRead[$bookId]['total_rating'] / $allBooksRead[$bookId]['rating_count'];
        }

        return array_values($allBooksRead);
    }
}
