<?php
defined( 'ABSPATH' ) || exit;
?>
<section class="asn-directory" aria-labelledby="asn-directory-title">
    <h1 id="asn-directory-title" class="asn-directory__title">Explore members</h1>

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
        <button class="asn-button asn-directory__submit" type="submit">Search</button>
    </form>

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
</section>
