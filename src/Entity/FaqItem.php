<?php

namespace App\Entity;

use App\Repository\FaqItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqItemRepository::class)]
class FaqItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'faqItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Property $property = null;

    #[ORM\Column(length: 255)]
    private string $questionPt = '';

    #[ORM\Column(length: 255)]
    private string $questionEn = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $answerPt = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $answerEn = '';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $v): static { $this->property = $v; return $this; }
    public function getQuestionPt(): string { return $this->questionPt; }
    public function setQuestionPt(string $v): static { $this->questionPt = $v; return $this; }
    public function getQuestionEn(): string { return $this->questionEn; }
    public function setQuestionEn(string $v): static { $this->questionEn = $v; return $this; }
    public function getAnswerPt(): string { return $this->answerPt; }
    public function setAnswerPt(string $v): static { $this->answerPt = $v; return $this; }
    public function getAnswerEn(): string { return $this->answerEn; }
    public function setAnswerEn(string $v): static { $this->answerEn = $v; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }

    public function getQuestion(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->questionEn : $this->questionPt;
    }

    public function getAnswer(string $locale = 'pt'): string
    {
        return 'en' === $locale ? $this->answerEn : $this->answerPt;
    }
}
