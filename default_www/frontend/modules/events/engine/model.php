<?php

/**
 * In this file we store all generic functions that we will be using in the events module
 *
 * @package		frontend
 * @subpackage	events
 *
 * @author		Tijs Verkoyen <tijs@sumocoders.be>
 * @since		2.0
 */
class FrontendEventsModel implements FrontendTagsInterface
{
//	/**
//	 * Get an item
//	 *
//	 * @return	array
//	 * @param	string $URL		The URL for the item.
//	 */
//	public static function get($URL)
//	{
//		return (array) FrontendModel::getDB()->getRecord('SELECT i.id, i.revision_id, i.language, i.title, i.introduction, i.text,
//															c.name AS category_name, c.url AS category_url,
//															UNIX_TIMESTAMP(i.publish_on) AS publish_on, i.user_id,
//															i.allow_comments,
//															m.keywords AS meta_keywords, m.keywords_overwrite AS meta_keywords_overwrite,
//															m.description AS meta_description, m.description_overwrite AS meta_description_overwrite,
//															m.title AS meta_title, m.title_overwrite AS meta_title_overwrite,
//															m.url
//															FROM events_posts AS i
//															INNER JOIN events_categories AS c ON i.category_id = c.id
//															INNER JOIN meta AS m ON i.meta_id = m.id
//															WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND m.url = ?
//															LIMIT 1',
//															array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', (string) $URL));
//	}
//
//
	/**
	 * Get all items (at least a chunk)
	 *
	 * @return	array
	 * @param	int[optional] $limit		The number of items to get.
	 * @param	int[optional] $offset		The offset.
	 */
	public static function getAll($limit = 10, $offset = 0)
	{
		$ids = (array) FrontendModel::getDB()->getColumn('SELECT i.id
															FROM events AS i
															INNER JOIN meta AS m ON i.meta_id = m.id
															WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND
																((i.ends_on IS NOT NULL AND i.ends_on > ?) || (i.starts_on > ?))
															ORDER BY i.starts_on ASC, i.id DESC
															LIMIT ?, ?',
															array('active', FRONTEND_LANGUAGE, 'N',
																	FrontendModel::getUTCDate('Y-m-d H:i') .':00', FrontendModel::getUTCDate('Y-m-d') .' 00:00:00', FrontendModel::getUTCDate('Y-m-d') .' 00:00:00', (int) $offset, (int) $limit), 'revision_id');

		// no results?
		if(empty($ids)) return array();

		// return
		return self::getMultiple($ids);
	}


//	/**
//	 * Get all comments (at least a chunk)
//	 *
//	 * @return	array
//	 * @param	int[optional] $limit		The number of items to get.
//	 * @param	int[optional] $offset		The offset.
//	 */
//	public static function getAllComments($limit = 10, $offset = 0)
//	{
//		return (array) FrontendModel::getDB()->getRecords('SELECT i.id, UNIX_TIMESTAMP(i.created_on) AS created_on, i.author, i.text,
//															p.id AS post_id, p.title AS post_title, m.url AS post_url
//															FROM events_comments AS i
//															INNER JOIN events_posts AS p ON i.post_id = p.id AND i.language = p.language
//															INNER JOIN meta AS m ON p.meta_id = m.id
//															WHERE i.status = ? AND i.language = ?
//															GROUP BY i.id
//															ORDER BY i.created_on DESC
//															LIMIT ?, ?',
//															array('published', FRONTEND_LANGUAGE, (int) $offset, (int) $limit));
//	}
//
//
	/**
	 * Get the number of items
	 *
	 * @return	int
	 */
	public static function getAllCount()
	{
		return (int) FrontendModel::getDB()->getVar('SELECT COUNT(i.id) AS count
														FROM events AS i
														WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND
															((i.ends_on IS NOT NULL AND i.ends_on > ?) || (i.starts_on > ?)) ',
														array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', FrontendModel::getUTCDate('Y-m-d') .' 00:00:00', FrontendModel::getUTCDate('Y-m-d') .' 00:00:00'));
	}


	/**
	 * Get all items between a start and end-date
	 *
	 * @return	array
	 * @param	int $start				The start date as a UNIX-timestamp.
	 * @param	int $end				The end date as a UNIX-timestamp.
	 * @param	int[optional] $limit	The number of items to get.
	 * @param	int[optional] $offset	The offset.
	 */
	public static function getAllForDateRange($start, $end, $limit = 10, $offset = 0)
	{
		// redefine
		$start = (int) $start;
		$end = (int) $end;
		$limit = (int) $limit;
		$offset = (int) $offset;

		// get the items
		$ids = (array) FrontendModel::getDB()->getColumn('SELECT i.id
															FROM events AS i
															INNER JOIN meta AS m ON i.meta_id = m.id
															WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND i.starts_on BETWEEN ? AND ?
															ORDER BY i.starts_on ASC, i.id DESC
															LIMIT ?, ?',
															array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', FrontendModel::getUTCDate('Y-m-d H:i', $start), FrontendModel::getUTCDate('Y-m-d H:i', $end), $offset, $limit), 'revision_id');

		// no results?
		if(empty($ids)) return array();

		// return
		return self::getMultiple($ids);
	}


	/**
	 * Get the number of items in a date range
	 *
	 * @return	int
	 * @param	int $start	The startdate as a UNIX-timestamp.
	 * @param	int $end	The enddate as a UNIX-timestamp.
	 */
	public static function getAllForDateRangeCount($start, $end)
	{
		// redefine
		$start = (int) $start;
		$end = (int) $end;

		// return the number of items
		return (int) FrontendModel::getDB()->getVar('SELECT COUNT(i.id)
														FROM events AS i
														WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND i.starts_on BETWEEN ? AND ?',
														array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', FrontendModel::getUTCDate('Y-m-d H:i:s', $start), FrontendModel::getUTCDate('Y-m-d H:i:s', $end)));
	}


//	/**
//	 * Get the comments for an item
//	 *
//	 * @return	array
//	 * @param	int $id		The ID of the item to get the comments for.
//	 */
//	public static function getComments($id)
//	{
//		// get the comments
//		$comments = (array) FrontendModel::getDB()->getRecords('SELECT c.id, UNIX_TIMESTAMP(c.created_on) AS created_on, c.text, c.data,
//																c.author, c.email, c.website
//																FROM events_comments AS c
//																WHERE c.post_id = ? AND c.status = ? AND c.language = ?
//																ORDER BY c.created_on ASC',
//																array((int) $id, 'published', FRONTEND_LANGUAGE));
//
//		// loop comments and create gravatar id
//		foreach($comments as &$row) $row['gravatar_id'] = md5($row['email']);
//
//		// return
//		return $comments;
//	}
//
//
//	/**
//	 * Get a draft for an item
//	 *
//	 * @return	array
//	 * @param	string $URL		The URL for the item to get.
//	 * @param	int $draft		The draftID.
//	 */
//	public static function getDraft($URL, $draft)
//	{
//		return (array) FrontendModel::getDB()->getRecord('SELECT i.id, i.revision_id, i.language, i.title, i.introduction, i.text,
//															c.name AS category_name, c.url AS category_url,
//															UNIX_TIMESTAMP(i.publish_on) AS publish_on, i.user_id,
//															i.allow_comments,
//															m.keywords AS meta_keywords, m.keywords_overwrite AS meta_keywords_overwrite,
//															m.description AS meta_description, m.description_overwrite AS meta_description_overwrite,
//															m.title AS meta_title, m.title_overwrite AS meta_title_overwrite,
//															m.url
//															FROM events_posts AS i
//															INNER JOIN events_categories AS c ON i.category_id = c.id
//															INNER JOIN meta AS m ON i.meta_id = m.id
//															WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.revision_id = ? AND m.url = ?
//															LIMIT 1',
//															array('draft', FRONTEND_LANGUAGE, 'N', (int) $draft, (string) $URL));
//	}
//
//
	/**
	 * Fetch the list of tags for a list of items
	 *
	 * @return	array
	 * @param	array $ids	The ids of the items to grab.
	 */
	public static function getForTags(array $ids)
	{
		// fetch items
		$items = (array) FrontendModel::getDB()->getRecords('SELECT i.title, m.url
																FROM events_posts AS i
																INNER JOIN meta AS m ON m.id = i.meta_id
																WHERE i.status = ? AND i.hidden = ? AND i.revision_id IN ('. implode(',', $ids) .')
																ORDER BY i.publish_on DESC',
																array('active', 'N'));

		// has items
		if(!empty($items))
		{
			// init var
			$link = FrontendNavigation::getURLForBlock('events', 'detail');

			// reset url
			foreach($items as &$row) $row['full_url'] = $link .'/'. $row['url'];
		}

		// return
		return $items;
	}


	/**
	 * Get the id of an item by the full URL of the current page.
	 * Selects the proper part of the full URL to get the item's id from the database.
	 *
	 * @return	int					The id that corresponds with the given full URL.
	 * @param	FrontendURL $URL	The current URL.
	 */
	public static function getIdForTags(FrontendURL $URL)
	{
		// select the proper part of the full URL
		$itemURL = (string) $URL->getParameter(1);

		// return the item
		return self::get($itemURL);
	}


	public static function getMultiple(array $ids)
	{
		// validate
		if(empty($ids)) return array();

		// get items
		$items = FrontendModel::getDB()->getRecords('SELECT i.id, i.revision_id, i.language, i.title, UNIX_TIMESTAMP(i.starts_on) AS starts_on, UNIX_TIMESTAMP(i.ends_on) AS ends_on, i.introduction, i.text, i.num_comments AS comments_count,
														UNIX_TIMESTAMP(i.publish_on) AS publish_on, i.allow_comments,
														m.keywords AS meta_keywords, m.keywords_overwrite AS meta_keywords_overwrite,
														m.description AS meta_description, m.description_overwrite AS meta_description_overwrite,
														m.title AS meta_title, m.title_overwrite AS meta_title_overwrite,
														m.url
														FROM events AS i
														INNER JOIN meta AS m ON i.meta_id = m.id
														WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND i.id IN('. implode(',', $ids) .')',
														array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00'), 'id');

		// init var
		$revisionIds = array();
		$link = FrontendNavigation::getURLForBlock('events', 'detail');
		$return = array();

		// loop
		foreach($ids as $id)
		{
			// skip non existing items
			if(!isset($items[$id])) continue;

			// ids
			$revisionIds[] = (int) $items[$id]['revision_id'];

			// URLs
			$items[$id]['full_url'] = $link .'/'. $items[$id]['url'];

			// comments
			if($items[$id]['comments_count'] > 0) $items[$id]['comments'] = true;
			if($items[$id]['comments_count'] > 1) $items[$id]['comments_multiple'] = true;

			// add
			$return[$items[$id]['revision_id']] = $items[$id];
		}

		// get all tags
		$tags = FrontendTagsModel::getForMultipleItems('events', $revisionIds);

		// loop tags and add to correct item
		foreach($tags as $postId => $tags)
		{
			if(isset($return[$postId])) $return[$postId]['tags'] = $tags;
		}

		// return
		return $return;
	}



//	/**
//	 * Get an array with the previous and the next post
//	 *
//	 * @return	array
//	 * @param	int $id		The id of the current item.
//	 */
//	public static function getNavigation($id)
//	{
//		// redefine
//		$id = (int) $id;
//
//		// get db
//		$db = FrontendModel::getDB();
//
//		// get date for current item
//		$date = (string) $db->getVar('SELECT i.publish_on
//										FROM events_posts AS i
//										WHERE i.id = ?',
//										array($id));
//
//		// validate
//		if($date == '') return array();
//
//		// init var
//		$navigation = array();
//
//		// get previous post
//		$navigation['previous'] = $db->getRecord('SELECT i.id, i.title, m.url
//													FROM events_posts AS i
//													INNER JOIN meta AS m ON i.meta_id = m.id
//													WHERE i.id != ? AND i.status = ? AND i.hidden = ? AND i.language = ? AND i.publish_on <= ?
//													ORDER BY i.publish_on DESC
//													LIMIT 1',
//													array($id, 'active', 'N', FRONTEND_LANGUAGE, $date));
//
//		// get next post
//		$navigation['next'] = $db->getRecord('SELECT i.id, i.title, m.url
//												FROM events_posts AS i
//												INNER JOIN meta AS m ON i.meta_id = m.id
//												WHERE i.id != ? AND i.status = ? AND i.hidden = ? AND i.language = ? AND i.publish_on > ?
//												ORDER BY i.publish_on ASC
//												LIMIT 1',
//												array($id, 'active', 'N', FRONTEND_LANGUAGE, $date));
//
//		// return
//		return $navigation;
//	}
//
//
//	/**
//	 * Get recent comments
//	 *
//	 * @return	array
//	 * @param	int[optional] $limit	The number of comments to get.
//	 */
//	public static function getRecentComments($limit = 5)
//	{
//		// redefine
//		$limit = (int) $limit;
//
//		// init var
//		$return = array();
//
//		// get comments
//		$comments = (array) FrontendModel::getDB()->getRecords('SELECT c.id, c.author, c.website, c.email, UNIX_TIMESTAMP(c.created_on) AS created_on, c.text,
//																i.id AS post_id, i.title AS post_title,
//																m.url AS post_url
//																FROM events_comments AS c
//																INNER JOIN events_posts AS i ON c.post_id = i.id AND c.language = i.language
//																INNER JOIN meta AS m ON i.meta_id = m.id
//																WHERE c.status = ? AND i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ?
//																ORDER BY c.created_on DESC
//																LIMIT ?',
//																array('published', 'active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', $limit));
//
//		// validate
//		if(empty($comments)) return $return;
//
//		// get link
//		$link = FrontendNavigation::getURLForBlock('events', 'detail');
//
//		// loop comments
//		foreach($comments as &$row)
//		{
//			// add some URLs
//			$row['post_full_url'] = $link .'/'. $row['post_url'];
//			$row['full_url'] = $link .'/'. $row['post_url'] .'#comment-'. $row['id'];
//			$row['gravatar_id'] = md5($row['email']);
//		}
//
//		// return
//		return $comments;
//	}
//
//
//	/**
//	 * Get related items based on tags
//	 *
//	 * @return	array
//	 * @param	int $id					The id of the item to get related items for.
//	 * @param	int[optional] $limit	The maximum number of items to retrieve.
//	 */
//	public static function getRelated($id, $limit = 5)
//	{
//		// redefine
//		$id = (int) $id;
//		$limit = (int) $limit;
//
//		// get the related IDs
//		$relatedIDs = (array) FrontendTagsModel::getRelatedItemsByTags($id, 'events', 'events');
//
//		// no items
//		if(empty($relatedIDs)) return array();
//
//		// get link
//		$link = FrontendNavigation::getURLForBlock('events', 'detail');
//
//		// get items
//		$items = (array) FrontendModel::getDB()->getRecords('SELECT i.id, i.title, m.url
//																FROM events_posts AS i
//																INNER JOIN meta AS m ON i.meta_id = m.id
//																WHERE i.status = ? AND i.language = ? AND i.hidden = ? AND i.publish_on <= ? AND i.id IN('. implode(',', $relatedIDs) .')
//																ORDER BY i.publish_on DESC, i.id DESC
//																LIMIT ?',
//																array('active', FRONTEND_LANGUAGE, 'N', FrontendModel::getUTCDate('Y-m-d H:i') .':00', $limit), 'id');
//
//		// loop items
//		foreach($items as &$row)
//		{
//			$row['full_url'] = $link .'/'. $row['url'];
//		}
//
//		// return
//		return $items;
//	}
//
//
//	/**
//	 * Get a revision for an item
//	 *
//	 * @return	array
//	 * @param	string $URL		The URL for the item to get.
//	 * @param	int $revision	The revisionID.
//	 */
//	public static function getRevision($URL, $revision)
//	{
//		return (array) FrontendModel::getDB()->getRecord('SELECT i.id, i.revision_id, i.language, i.title, i.introduction, i.text,
//															c.name AS category_name, c.url AS category_url,
//															UNIX_TIMESTAMP(i.publish_on) AS publish_on, i.user_id,
//															i.allow_comments,
//															m.keywords AS meta_keywords, m.keywords_overwrite AS meta_keywords_overwrite,
//															m.description AS meta_description, m.description_overwrite AS meta_description_overwrite,
//															m.title AS meta_title, m.title_overwrite AS meta_title_overwrite,
//															m.url
//															FROM events_posts AS i
//															INNER JOIN events_categories AS c ON i.category_id = c.id
//															INNER JOIN meta AS m ON i.meta_id = m.id
//															WHERE i.language = ? AND i.revision_id = ? AND m.url = ?
//															LIMIT 1',
//															array(FRONTEND_LANGUAGE, (int) $revision, (string) $URL));
//	}
//
//
//	/**
//	 * Inserts a new comment
//	 *
//	 * @return	int
//	 * @param	array $comment	The comment to add.
//	 */
//	public static function insertComment(array $comment)
//	{
//		// get db
//		$db = FrontendModel::getDB(true);
//
//		// insert comment
//		$comment['id'] = (int) $db->insert('events_comments', $comment);
//
//		// recalculate if published
//		if($comment['status'] == 'published')
//		{
//			// num comments
//			$numComments = (int) FrontendModel::getDB()->getVar('SELECT COUNT(i.id) AS comment_count
//																	FROM events_comments AS i
//																	INNER JOIN events_posts AS p ON i.post_id = p.id AND i.language = p.language
//																	WHERE i.status = ? AND i.post_id = ? AND i.language = ? AND p.status = ?
//																	GROUP BY i.post_id',
//																	array('published', $comment['post_id'], FRONTEND_LANGUAGE, 'active'));
//
//			// update num comments
//			$db->update('events_posts', array('num_comments' => $numComments), 'id = ?', $comment['post_id']);
//		}
//
//		// return new id
//		return $comment['id'];
//	}
//
//
//	/**
//	 * Get moderation status for an author
//	 *
//	 * @return	bool
//	 * @param	string $author	The name for the author.
//	 * @param	string $email	The emailaddress for the author.
//	 */
//	public static function isModerated($author, $email)
//	{
//		return (bool) FrontendModel::getDB()->getVar('SELECT COUNT(c.id)
//														FROM events_comments AS c
//														WHERE c.status = ? AND c.author = ? AND c.email = ?',
//														array('published', (string) $author, (string) $email));
//	}
//
//
//	/**
//	 * Notify the admin
//	 *
//	 * @return	void
//	 * @param	array $comment	The comment that was submitted.
//	 */
//	public static function notifyAdmin(array $comment)
//	{
//		// don't notify admin in case of spam
//		if($comment['status'] == 'spam') return;
//
//		// build data for pushnotification
//		if($comment['status'] == 'moderation') $alert = array('loc-key' => 'NEW_COMMENT_TO_MODERATE');
//		else $alert = array('loc-key' => 'NEW_COMMENT');
//
//		// get count of unmoderated items
//		$badge = (int) FrontendModel::getDB()->getVar('SELECT COUNT(i.id)
//														FROM events_comments AS i
//														WHERE i.status = ? AND i.language = ?
//														GROUP BY i.status',
//														array('moderation', FRONTEND_LANGUAGE));
//
//		// reset if needed
//		if($badge == 0) $badge = null;
//
//		// build data
//		$data = array('data' => array('endpoint' => SITE_URL .'/api/1.0', 'comment_id' => $comment['id']));
//
//		// push it
//		FrontendModel::pushToAppleApp($alert, $badge, null, $data);
//
//		// get settings
//		$notifyByMailOnComment = FrontendModel::getModuleSetting('events', 'notify_by_email_on_new_comment', false);
//		$notifyByMailOnCommentToModerate = FrontendModel::getModuleSetting('events', 'notify_by_email_on_new_comment_to_moderate', false);
//
//		// create URLs
//		$URL = SITE_URL . FrontendNavigation::getURLForBlock('events', 'detail') .'/'. $comment['post_url'] .'#comment-'. $comment['id'];
//		$backendURL = SITE_URL . FrontendNavigation::getBackendURLForBlock('comments', 'events') .'#tabModeration';
//
//		// notify on all comments
//		if($notifyByMailOnComment)
//		{
//			// comment to moderate
//			if($comment['status'] == 'moderation')
//			{
//				// set variables
//				$variables['message'] = vsprintf(FL::getMessage('EventsEmailNotificationsNewCommentToModerate'), array($comment['author'], $URL, $comment['post_title'], $backendURL));
//			}
//
//			// comment was published
//			elseif($comment['status'] == 'published')
//			{
//				// set variables
//				$variables['message'] = vsprintf(FL::getMessage('EventsEmailNotificationsNewComment'), array($comment['author'], $URL, $comment['post_title']));
//			}
//
//			// send the mail
//			FrontendMailer::addEmail(FL::getMessage('NotificationSubject'), FRONTEND_CORE_PATH .'/layout/templates/mails/notification.tpl', $variables);
//		}
//
//		// only notify on new comments to moderate and if the comment is one to moderate
//		elseif($notifyByMailOnCommentToModerate && $comment['status'] == 'moderation')
//		{
//				// set variables
//				$variables['message'] = vsprintf(FL::getMessage('EventsEmailNotificationsNewCommentToModerate'), array($comment['author'], $URL, $comment['post_title'], $backendURL));
//
//			// send the mail
//			FrontendMailer::addEmail(FL::getMessage('NotificationSubject'), FRONTEND_CORE_PATH .'/layout/templates/mails/notification.tpl', $variables);
//		}
//	}
//
//
//	/**
//	 * Parse the search results for this module
//	 *
//	 * Note: a module's search function should always:
//	 * 		- accept an array of entry id's
//	 * 		- return only the entries that are allowed to be displayed, with their array's index being the entry's id
//	 *
//	 *
//	 * @return	array
//	 * @param	array $ids		The ids of the found results.
//	 */
//	public static function search(array $ids)
//	{
//		// get items
//		$items = (array) FrontendModel::getDB()->getRecords('SELECT i.id, i.title, i.introduction, i.text, m.url
//																FROM events_posts AS i
//																INNER JOIN meta AS m ON i.meta_id = m.id
//																WHERE i.status = ? AND i.hidden = ? AND i.language = ? AND i.publish_on <= ? AND i.id IN ('. implode(',', $ids) .')',
//																array('active', 'N', FRONTEND_LANGUAGE, date('Y-m-d H:i') .':00'), 'id');
//
//		// prepare items for search
//		foreach($items as &$item)
//		{
//			$item['full_url'] = FrontendNavigation::getURLForBlock('events', 'detail') .'/'. $item['url'];
//		}
//
//		// return
//		return $items;
//	}
}

?>