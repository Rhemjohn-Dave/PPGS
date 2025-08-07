<?php
checkRoleAccess(getUserRole()); // Ensure user is logged in
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Task Request Form</h2>
                </div>
                <div class="card-body card-padding">
                    <form id="taskRequestForm" action="functions/task_request.php" method="POST">
                        <!-- Base Fields -->
                        <div class="form-group">
                            <div class="fg-line">
                                <input type="text" class="form-control" name="title" placeholder="Task Title" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="fg-line">
                                <textarea class="form-control" name="description" placeholder="Task Description" required></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="fg-line">
                                <select class="form-control" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="fg-line">
                                <select class="form-control" name="department" required>
                                    <option value="">Select Department</option>
                                    <!-- Departments will be populated from database -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="fg-line">
                                <select class="form-control" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="printing">Risograph / Printing</option>
                                    <option value="repairs">Repairs</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="instructional">Instructional Materials</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Dynamic Fields -->
                        <div id="printingFields" class="category-fields" style="display: none;">
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="number" class="form-control" name="copies" placeholder="Number of Copies">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <select class="form-control" name="paper_size">
                                        <option value="">Select Paper Size</option>
                                        <option value="a4">A4</option>
                                        <option value="legal">Legal</option>
                                        <option value="letter">Letter</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <select class="form-control" name="paper_type">
                                        <option value="">Select Paper Type</option>
                                        <option value="bond">Bond Paper</option>
                                        <option value="colored">Colored Paper</option>
                                        <option value="cardboard">Cardboard</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="repairsFields" class="category-fields" style="display: none;">
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" class="form-control" name="equipment_name" placeholder="Equipment Name">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <textarea class="form-control" name="problem_description" placeholder="Problem Description"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" class="form-control" name="technician" placeholder="Preferred Technician (Optional)">
                                </div>
                            </div>
                        </div>
                        
                        <div id="maintenanceFields" class="category-fields" style="display: none;">
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" class="form-control" name="area" placeholder="Area">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <select class="form-control" name="maintenance_type">
                                        <option value="">Select Maintenance Type</option>
                                        <option value="preventive">Preventive</option>
                                        <option value="corrective">Corrective</option>
                                        <option value="predictive">Predictive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="instructionalFields" class="category-fields" style="display: none;">
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" class="form-control" name="subject" placeholder="Subject/Department">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" class="form-control" name="grade_level" placeholder="Grade Level">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="file" class="form-control" name="template">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('category').addEventListener('change', function() {
    // Hide all category fields
    document.querySelectorAll('.category-fields').forEach(field => {
        field.style.display = 'none';
    });
    
    // Show selected category fields
    const selectedCategory = this.value;
    if (selectedCategory) {
        document.getElementById(selectedCategory + 'Fields').style.display = 'block';
    }
});
</script> 