<?php


namespace MundiPagg\MundiPagg\Model;

use MundiPagg\MundiPagg\Api\CardsRepositoryInterface;
use MundiPagg\MundiPagg\Api\Data\CardsSearchResultsInterfaceFactory;
use MundiPagg\MundiPagg\Api\Data\CardsInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use MundiPagg\MundiPagg\Model\ResourceModel\Cards as ResourceCards;
use MundiPagg\MundiPagg\Model\ResourceModel\Cards\CollectionFactory as CardsCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class CardsRepository implements CardsRepositoryInterface
{

    protected $resource;

    protected $cardsFactory;

    protected $cardsCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataCardsFactory;

    private $storeManager;


    /**
     * @param ResourceCards $resource
     * @param CardsFactory $cardsFactory
     * @param CardsInterfaceFactory $dataCardsFactory
     * @param CardsCollectionFactory $cardsCollectionFactory
     * @param CardsSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceCards $resource,
        CardsFactory $cardsFactory,
        CardsInterfaceFactory $dataCardsFactory,
        CardsCollectionFactory $cardsCollectionFactory,
        CardsSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->cardsFactory = $cardsFactory;
        $this->cardsCollectionFactory = $cardsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCardsFactory = $dataCardsFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \MundiPagg\MundiPagg\Api\Data\CardsInterface $cards
    ) {
        try {
            $cards->getResource()->save($cards);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the cards: %1',
                $exception->getMessage()
            ));
        }

        return $cards;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($cardsId)
    {
        $cards = $this->cardsFactory->create();
        $cards->getResource()->load($cards, $cardsId);
        if (!$cards->getId()) {
            throw new NoSuchEntityException(__('Cards with id "%1" does not exist.', $cardsId));
        }

        return $cards;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->cardsCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \MundiPagg\MundiPagg\Api\Data\CardsInterface $cards
    ) {
        try {
            $cards->getResource()->delete($cards);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Cards: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($cardsId)
    {
        return $this->delete($this->getById($cardsId));
    }
}