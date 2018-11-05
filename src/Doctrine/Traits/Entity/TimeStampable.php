<?php

namespace Siworks\Slim\Doctrine\Traits\Entity;

trait TimeStampable
{
    /**
     * @var \DateTIme
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $created_at;

    /**
     * @var \DateTIme
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updated_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersistListener()
    {
        $this->created_at = new \DateTime("NOW");
        $this->updated_at = new \DateTime("NOW");
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdateListener()
    {
        if(!$this->created_at instanceof \DateTime){
            $this->created_at = new \DateTime($this->created_at);
        }                
        $this->updated_at = new \DateTime("NOW");
    }

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created_at;
    }

    /**
     * Sets created.
     *
     * @param \DateTime $created_at
     *
     * @return $this
     */
    public function setCreated( \DateTime $date )
    {
        $this->created_at = $date;

        return $this;
    }

    /**
     * Returns updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated_at;
    }

    /**
     * Sets updated.
     *
     * @param \DateTime $updated
     *
     * @return $this
     */
    public function setUpdated( \DateTime $date )
    {
        $this->updated_at = $date;

        return $this;
    }
}
