<?php
class PostEntity {
    private int $id;
    private string $title;
    private string $content;
    private string $image;
    private string $createdAt;
    private int $authorId;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->image = $data['image'] ?? 'default-post.jpg';
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->authorId = (int)($data['author_id'] ?? 0);
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image' => $this->image,
            'created_at' => $this->createdAt
        ];
    }

    // Getters
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getImage() { return $this->image; }
}