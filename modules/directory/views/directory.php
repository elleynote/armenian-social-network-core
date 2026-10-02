<?php
defined( 'ABSPATH' ) || exit;

$saveable_filters = array(
    'q' => $filters['q'],
    'country' => $filters['country'],
    'dialect' => $filters['dialect'],
    'proficiency' => $filters['proficiency'],
    'gender' => $filters['gender'],
    'job_title' => $filters['job_title'],
    'here_for' => $filters['here_for'],
    'age_min' => $filters['age_min'],
    'age_max' => $filters['age_max'],
    'recent' => $filters['recent'] ? '1' : '',
    'favorites' => $filters['favorites'] ? '1' : '',
);
$has_filters = \ASN\Core\Directory\Directory_Query::has_active_filters( $filters );
?>
<section class="asn-directory" aria-labelledby="asn-directory-title">
    <h1 id="asn-directory-title" class="asn-directory__title">Explore members</h1>

    <?php if ( '' !== $feature_notice && isset( $notice_messages[ $feature_notice ] ) ) : ?>
        <div class="asn-directory__notice"><?php echo esc_html( $notice_messages[ $feature_notice ] ); ?></div>
    <?php endif; ?>

    <?php if ( ! empty( $saved_searches ) ) : ?>
        <section class="asn-directory__saved-searches" aria-labelledby="asn-saved-searches-title">
            <div class="asn-directory__section-heading">
                <h2 id="asn-saved-searches-title">Saved searches</h2>
            </div>
            <div class="asn-directory__saved-search-list">
                <?php foreach ( $saved_searches as $saved_search ) : ?>
                    <?php
                    $saved_url = $form_action . '?' . http_build_query( $saved_search['filters'], '', '&', PHP_QUERY_RFC3986 );
                    ?>
                    <div class="asn-directory__saved-search">
                        <a href="<?php echo esc_url( $saved_url ); ?>"><?php echo esc_html( $saved_search['label'] ); ?></a>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="asn_delete_saved_search">
                            <input type="hidden" name="search_id" value="<?php echo esc_attr( $saved_search['id'] ); ?>">
                            <input type="hidden" name="return_url" value="<?php echo esc_url( $form_action ); ?>">
                            <?php wp_nonce_field( 'asn_delete_search_' . $saved_search['id'], 'asn_discovery_nonce' ); ?>
                            <button type="submit" aria-label="Delete saved search">&times;</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <form class="asn-directory__filters" method="get" action="<?php echo esc_url( $form_action ); ?>">
        <label class="asn-field">
            <span class="asn-field__label">Search</span>
            <input class="asn-field__input" type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>">
        </label>
        <label class="asn-field">
            <span class="asn-field__label">Country</span>
            <input class="asn-field__input" type="text" name="country" value="<?php echo esc_attr( $filters['country'] ); ?>">
        </label>
        <label class="asn-field">
            <span class="asn-field__label">Dialect</span>
            <select class="asn-field__input" name="dialect">
                <option value="">All</option>
                <option value="eastern"<?php echo 'eastern' === $filters['dialect'] ? ' selected' : ''; ?>>Eastern Armenian</option>
                <option value="western"<?php echo 'western' === $filters['dialect'] ? ' selected' : ''; ?>>Western Armenian</option>
            </select>
        </label>
        <label class="asn-field">
            <span class="asn-field__label">Proficiency</span>
            <select class="asn-field__input" name="proficiency">
                <option value="">All</option>
                <?php foreach ( array( 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'fluent' => 'Fluent' ) as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"<?php echo $value === $filters['proficiency'] ? ' selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="asn-button asn-directory__submit" type="submit" data-asn-directory-submit>Search</button>

        <details class="asn-directory__advanced"<?php echo $has_filters && ( $filters['gender'] || $filters['job_title'] || $filters['here_for'] || $filters['age_min'] || $filters['age_max'] || $filters['recent'] || $filters['favorites'] ) ? ' open' : ''; ?>>
            <summary>Advanced filters</summary>
            <div class="asn-directory__advanced-grid">
                <label class="asn-field">
                    <span class="asn-field__label">Age from</span>
                    <input class="asn-field__input" type="number" min="1" max="120" name="age_min" value="<?php echo $filters['age_min'] ? esc_attr( (string) $filters['age_min'] ) : ''; ?>">
                </label>
                <label class="asn-field">
                    <span class="asn-field__label">Age to</span>
                    <input class="asn-field__input" type="number" min="1" max="120" name="age_max" value="<?php echo $filters['age_max'] ? esc_attr( (string) $filters['age_max'] ) : ''; ?>">
                </label>
                <label class="asn-field">
                    <span class="asn-field__label">Gender</span>
                    <select class="asn-field__input" name="gender">
                        <option value="">All</option>
                        <?php foreach ( \ASN\Core\Profiles\Legacy_Profile_Contract::gender_options() as $gender ) : ?>
                            <option value="<?php echo esc_attr( $gender ); ?>"<?php echo $gender === $filters['gender'] ? ' selected' : ''; ?>><?php echo esc_html( $gender ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="asn-field">
                    <span class="asn-field__label">Job title</span>
                    <input class="asn-field__input" type="text" name="job_title" value="<?php echo esc_attr( $filters['job_title'] ); ?>">
                </label>
                <label class="asn-field">
                    <span class="asn-field__label">I&#8217;m here for</span>
                    <select class="asn-field__input" name="here_for">
                        <option value="">All</option>
                        <?php foreach ( \ASN\Core\Features\Member_Features::here_for_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"<?php echo $key === $filters['here_for'] ? ' selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="asn-directory__check">
                    <input type="checkbox" name="recent" value="1"<?php echo $filters['recent'] ? ' checked' : ''; ?>>
                    <span>Recently active</span>
                </label>
                <label class="asn-directory__check">
                    <input type="checkbox" name="favorites" value="1"<?php echo $filters['favorites'] ? ' checked' : ''; ?>>
                    <span>Saved profiles only</span>
                </label>
            </div>
        </details>
    </form>

    <?php if ( get_current_user_id() > 0 && $has_filters ) : ?>
        <form class="asn-directory__save-search" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="asn_save_search">
            <input type="hidden" name="return_url" value="<?php echo esc_url( $request_uri ); ?>">
            <?php foreach ( $saveable_filters as $key => $value ) : ?>
                <?php if ( '' !== (string) $value && 0 !== $value ) : ?>
                    <input type="hidden" name="filters[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <?php wp_nonce_field( 'asn_save_search', 'asn_discovery_nonce' ); ?>
            <button class="asn-directory__secondary-action" type="submit">Save this search</button>
        </form>
    <?php endif; ?>

    <?php if ( ! empty( $suggested_members ) ) : ?>
        <section class="asn-directory__suggested" aria-labelledby="asn-suggested-title">
            <div class="asn-directory__section-heading">
                <h2 id="asn-suggested-title">Suggested members</h2>
                <p>Members with profile details in common with you.</p>
            </div>
            <div class="asn-directory__grid asn-directory__grid--suggested">
                <?php foreach ( $suggested_members as $member ) : ?>
                    <?php require __DIR__ . '/member-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="asn-directory__results" data-asn-directory-results aria-live="polite">
    <?php if ( empty( $members ) ) : ?>
        <div class="asn-directory__empty"><p><?php echo esc_html( 'No members found.' ); ?></p></div>
    <?php else : ?>
        <div class="asn-directory__grid">
            <?php foreach ( $members as $member ) : ?>
                <?php require __DIR__ . '/member-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( $result['pages'] > 1 ) : ?>
        <nav class="asn-pagination" aria-label="Member directory pages">
            <?php foreach ( $pagination_urls as $page_number => $url ) : ?>
                <a class="asn-pagination__link<?php echo (int) $result['page'] === (int) $page_number ? ' asn-pagination__link--current' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo (int) $result['page'] === (int) $page_number ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $page_number ); ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    </div>
</section>
