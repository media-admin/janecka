<?php

function wp_all_export_posts_where($where)
{
	if ( ! empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session() )
	{
		// manual export run
		$customWhere = PMXE_Plugin::$session->get('whereclause');		
		$where .= $customWhere;
	}
	else
	{
		// cron job execution
		if ( ! empty(XmlExportEngine::$exportOptions['whereclause']) ) $where .= XmlExportEngine::$exportOptions['whereclause'];		
	}


    if(isset(XmlExportEngine::$exportOptions['enable_real_time_exports']) && XmlExportEngine::$exportOptions['enable_real_time_exports'] ) {

	    // Real-Time Exports
        if ( ! empty(XmlExportEngine::$exportOptions['whereclause']) ) $where .= XmlExportEngine::$exportOptions['whereclause'];
    }

	return $where;
}