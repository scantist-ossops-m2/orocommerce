<?php

namespace Oro\Bundle\RedirectBundle\Async;

/**
 * Redirect bundle MQ Topics holder.
 * @deprecated Will be removed in 5.1, use MQ topic classes instead:
 * {@see \Oro\Component\MessageQueue\Topic\TopicInterface}
 */
class Topics
{
    const GENERATE_DIRECT_URL_FOR_ENTITIES = 'oro.redirect.generate_direct_url.entity';
    const JOB_GENERATE_DIRECT_URL_FOR_ENTITIES = 'oro.redirect.job.generate_direct_url.entity';
    const REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE = 'oro.redirect.regenerate_direct_url.entity_type';
    const REMOVE_DIRECT_URL_FOR_ENTITY_TYPE = 'oro.redirect.remove_direct_url.entity_type';
    const SYNC_SLUG_REDIRECTS = 'oro.redirect.generate_slug_redirects';
    const CALCULATE_URL_CACHE_MASS = 'oro.redirect.calculate_cache.mass';
    const PROCESS_CALCULATE_URL_CACHE = 'oro.redirect.calculate_cache';
}