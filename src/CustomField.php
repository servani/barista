<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * CustomField
 */
class CustomField
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $attributes;

    /**
     * @var \Post
     */
    private $post;

    /**
     * @var \CfType
     */
    private $cfType;


    /**
     * Set id
     *
     * @param integer $id
     * @return CustomField
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return CustomField
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CustomField
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set attributes
     *
     * @param string $attributes
     * @return CustomField
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get attributes
     *
     * @return string 
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set post
     *
     * @param \Post $post
     * @return CustomField
     */
    public function setPost(\Post $post = null)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post
     *
     * @return \Post 
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set cfType
     *
     * @param \CfType $cfType
     * @return CustomField
     */
    public function setCfType(\CfType $cfType = null)
    {
        $this->cfType = $cfType;

        return $this;
    }

    /**
     * Get cfType
     *
     * @return \CfType 
     */
    public function getCfType()
    {
        return $this->cfType;
    }
}
