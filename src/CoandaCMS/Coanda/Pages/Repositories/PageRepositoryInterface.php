<?php namespace CoandaCMS\Coanda\Pages\Repositories;

/**
 * Interface PageRepositoryInterface
 * @package CoandaCMS\Coanda\Pages\Repositories
 */
interface PageRepositoryInterface {

    /**
     * @param $id
     * @return mixed
     */
    public function find($id);

    /**
     * @param $id
     * @return mixed
     */
    public function findById($id);

    /**
     * @param $id
     * @return mixed
     */
    public function locationById($id);

    public function locationBySlug($slug);
    
    /**
     * @param $id
     * @return mixed
     */
    public function getByRemoteId($remote_id);

    /**
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function locations($limit, $offset);

    /**
     * @param $ids
     * @return mixed
     */
    public function findByIds($ids);

    /**
     * @param $page_id
     * @param $per_page
     * @return mixed
     */
    public function subPages($page_id, $per_page, $parameters);

    /**
     * @param $type
     * @param $user_id
     * @param $parent_page_id
     * @return mixed
     */
    public function create($type, $user_id, $parent_page_id);

    public function createAndPublish($type, $user_id, $parent_page_id, $data);

    public function updateAndPublish($page_id, $user_id, $parent_page_id, $data);

    /**
     * @param $type
     * @param $user_id
     * @return mixed
     */
    public function createHome($type, $user_id);

    public function createAndPublishHome($type, $user_id, $data);

    /**
     * @param $page_id
     * @param $version
     * @return mixed
     */
    public function getDraftVersion($page_id, $version);

    /**
     * @param $id
     * @return mixed
     */
    public function getVersionById($id);
    /**
     * @param $preview_key
     * @return mixed
     */
    public function getVersionByPreviewKey($preview_key);

    /**
     * @param $version
     * @param $data
     * @return mixed
     */
    public function saveDraftVersion($version, $data);

    /**
     * @param $version_id
     * @param $page_location_id
     * @return mixed
     */
    public function addNewVersionSlug($version_id, $page_location_id);

    /**
     * @param $version_id
     * @param $slug_id
     * @return mixed
     */
    public function removeVersionSlug($version_id, $slug_id);

    /**
     * @param $version
     * @return mixed
     */
    public function discardDraftVersion($version, $user_id);

    /**
     * @param $page_id
     * @param $user_id
     * @return mixed
     */
    public function draftsForUser($page_id, $user_id);

    /**
     * @param $version
     * @param $user_id
     * @param $urlRepository
     * @param $historyRepository
     * @return mixed
     */
    public function publishVersion($version, $user_id, $urlRepository, $historyRepository);

    /**
     * @param $version
     * @param $publish_handler
     * @param $data
     * @return mixed
     */
    public function executePublishHandler($version, $publish_handler, $data);

    /**
     * @param $page_id
     * @param $user_id
     * @return mixed
     */
    public function createNewVersion($page_id, $user_id);

    /**
     * @param $page_id
     * @param bool $permanent
     * @return mixed
     */
    public function deletePage($page_id, $permanent = false);

    /**
     * @param $pages_ids
     * @param bool $permanent
     * @return mixed
     */
    public function deletePages($pages_ids, $permanent = false);

    /**
     * @return mixed
     */
    public function trashed();

    /**
     * @param $page_id
     * @param $restore_sub_pages
     * @return mixed
     */
    public function restore($page_id, $restore_sub_pages);

    /**
     * @param $location_id
     * @param $new_order
     * @return mixed
     */
    public function updateLocationOrder($location_id, $new_order);

    /**
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function getPendingVersions($offset, $limit);

    /**
     * @return mixed
     */
    public function getHomePage();

    /**
     * @param $location_id
     * @param $new_sub_page_order
     * @return mixed
     */
    public function updateLocationSubPageOrder($location_id, $new_sub_page_order);

    /**
     * @param $version
     * @param $data
     * @return mixed
     */
    public function addVersionComment($version, $data);

    public function adminSearch($query);
}
