<?php
namespace Akeneo\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Value for a product field
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright Copyright (c) 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ORM\Table(name="AkeneoCatalog_Product_Value")
 * @ORM\Entity
 */
class Value
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Entity $product
     *
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="values")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
    * @var Field $field
    *
    * @ORM\ManyToOne(targetEntity="Field")
    */
    protected $field;

    /**
     * TODO : basic sample for basic EAV implementation, only varchar values
     * @var string $content
     *
     * @ORM\Column(name="data", type="string", length=255)
     */
    private $data;

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
     * Set data
     *
     * @param string $data
     * @return Value
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set product
     *
     * @param Akeneo\CatalogBundle\Entity\Entity $product
     * @return Value
     */
    public function setProduct(\Akeneo\CatalogBundle\Entity\Entity $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return Akeneo\CatalogBundle\Entity\Entity
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set field
     *
     * @param Akeneo\CatalogBundle\Entity\Field $field
     * @return Value
     */
    public function setField(\Akeneo\CatalogBundle\Entity\Field $field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return Akeneo\CatalogBundle\Entity\Field
     */
    public function getField()
    {
        return $this->field;
    }
}