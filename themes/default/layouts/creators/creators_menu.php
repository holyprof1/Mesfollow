<div class="creators_menu_wrapper_new">
    <div class="category_dropdown_container">
        <select class="category_dropdown" id="creatorCategorySelect" onchange="window.location.href=this.value">
            <!-- NORMAL USERS as default first option -->
            <?php
            $normalUserKey = 'normal_user'; // Adjust this to match your actual key in database
            $isNormalSelected = (iN_HelpSecure($pageCreator) === $normalUserKey || !$pageCreator) ? 'selected' : '';
            $normalUserUrl = iN_HelpSecure($base_url) . 'creators?creator=' . $normalUserKey;
            ?>
            <option value="<?php echo $normalUserUrl; ?>" <?php echo $isNormalSelected; ?>>
                Normal Users
            </option>
            
            <?php
            $categories = $iN->iN_GetCategories();
            if ($categories) {
                foreach ($categories as $caData) {
                    $categoryID = $caData['c_id'] ?? NULL;
                    $categoryKey = $caData['c_key'] ?? NULL;
                    
                    // Skip "normal_user" if it's already in the categories list
                    if ($categoryKey === 'normal_user') continue;
                    
                    $isSelected = (iN_HelpSecure($pageCreator) === iN_HelpSecure($categoryKey)) ? 'selected' : '';
                    $categoryUrl = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($categoryKey);
                    $categoryName = iN_HelpSecure($PROFILE_CATEGORIES[$categoryKey] ?? ucfirst($categoryKey));
                    ?>
                    <option value="<?php echo iN_HelpSecure($categoryUrl); ?>" <?php echo iN_HelpSecure($isSelected); ?>>
                        <?php echo iN_HelpSecure($categoryName); ?>
                    </option>
                    <?php
                    // Get subcategories
                    $subCategories = $iN->iN_CheckAndGetSubCat($categoryID);
                    if ($subCategories) {
                        foreach ($subCategories as $subData) {
                            $subKey = $subData['sc_key'] ?? NULL;
                            $subIsSelected = (iN_HelpSecure($pageCreator) === iN_HelpSecure($subKey)) ? 'selected' : '';
                            $subUrl = iN_HelpSecure($base_url) . 'creators?creator=' . iN_HelpSecure($subKey);
                            $subName = iN_HelpSecure($PROFILE_SUBCATEGORIES[$subKey] ?? ucfirst($subKey));
                            ?>
                            <option value="<?php echo iN_HelpSecure($subUrl); ?>" <?php echo iN_HelpSecure($subIsSelected); ?>>
                                &nbsp;&nbsp;&nbsp;↳ <?php echo iN_HelpSecure($subName); ?>
                            </option>
                            <?php
                        }
                    }
                }
            }
            ?>
        </select>
        <div class="dropdown_arrow">
            <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
                <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </div>
</div>