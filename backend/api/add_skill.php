<?php
// Header includes session_start() and navigation logic
require_once '../../frontend/components/header.php';
require_once '../config/db_config.php'; // Ensure this is present and no placeholder logic before it

// --- Homepage Specific Content ---
?>

<h2>Welcome to BOSTARTER!</h2>
<p>Your platform for funding innovative hardware and software projects. Discover, back, and bring ideas to life!</p>

<section id="featured-projects">
    <h3>Most Funded Open Projects</h3>
    <div class="projects-list">
        <?php
        $projects_html = "";
        $conn = null; // Initialize $conn
        try {
            $conn = get_db_connection(); // Directly call, it will die on error if connection fails

            $sql = "SELECT project_id, title, description, funding_goal, current_funding, creator_username, percentage_funded 
                    FROM view_top_funded_open_projects";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $projects_html .= "<div class='project-item'>";
                    $projects_html .= "<h4><a href='project_detail_page.php?id=" . htmlspecialchars($row['project_id']) . "'>" . htmlspecialchars($row['title']) . "</a></h4>";
                    $projects_html .= "<p><strong>Creator:</strong> " . htmlspecialchars($row['creator_username']) . "</p>";
                    $projects_html .= "<p>" . nl2br(htmlspecialchars(substr($row['description'], 0, 150))) . "...</p>"; // Short description
                    $projects_html .= "<p><strong>Funded:</strong> $" . number_format(floatval($row['current_funding']), 2) . " / $" . number_format(floatval($row['funding_goal']), 2) . " (" . number_format(floatval($row['percentage_funded']), 2) . "%)</p>";
                    // Progress bar using CSS classes
                    $percentage = min(100, floatval($row['percentage_funded'])); // Cap at 100 for display
                    $projects_html .= "<div class='progress-bar-container'>";
                    $projects_html .= "<div class='progress-bar-fill' style='width: " . $percentage . "%;'>";
                    $projects_html .= round($percentage) . "%";
                    $projects_html .= "</div></div>";
                    $projects_html .= "</div>";
                }
                $result->free();
            } elseif ($result) { // Query succeeded but no rows
                $projects_html = "<p><em>No open projects currently featured. Check back soon!</em></p>";
            } else { // Query failed
                // $conn->error might not be safe if $conn is null, but get_db_connection() dies on failure.
                // However, if query itself fails, $conn should be valid.
                $projects_html = "<p><em>Could not retrieve projects from the database. Error: " . ($conn ? htmlspecialchars($conn->error) : "Unknown database error") . "</em></p>";
                 // error_log("Homepage query failed: " . $conn->error);
            }
        } catch (Exception $e) {
            // This catch block might not be reached if get_db_connection dies,
            // but it's good for other potential exceptions.
            error_log("Exception on index.php: " . $e->getMessage());
            $projects_html = "<p><em>An unexpected error occurred while trying to load projects. Please try again later.</em></p>";
        } finally {
            if ($conn && $conn instanceof mysqli) {
                $conn->close();
            }
        }
        // If $projects_html is still empty, it means there were no projects or an error handled above.
        echo $projects_html;
        ?>
    </div>
</section>

<hr>

<section id="call-to-action">
    <h4>Ready to get started?</h4>
    <p>
        <a href="register_page.php" class="button">Join BOSTARTER</a> or 
        <a href="projects_page.php" class="button">Explore Projects</a>
    </p>
</section>


<?php
// --- End Homepage Specific Content ---

require_once 'footer.php';
?>
