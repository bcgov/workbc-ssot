# alb.tf

# Internal Load Balancer
resource "aws_alb" "main" {
  name = "ssot-lb"
  internal           = true
  load_balancer_type = "application"
  # App sg is required for drupal to call ssot
  security_groups    = [data.aws_security_group.web.id, data.aws_security_group.app.id]
  subnets            = module.network.aws_subnet_ids.web.ids 

  tags = var.common_tags
}

# Redirect all traffic from the ALB to the target group
resource "aws_alb_listener" "front_end" {
  load_balancer_arn = aws_alb.main.arn
  port              = "3000"
  protocol          = "HTTP"
  
  default_action {
    type             = "forward"
    target_group_arn = aws_alb_target_group.app.arn
  }
}

resource "aws_alb_target_group" "app" {
  name                 = "workbc-ssot-target-group-${substr(uuid(), 0, 3)}"
  port                 = 3000
  protocol             = "HTTP"
  vpc_id               = module.network.aws_vpc.id
  target_type          = "ip"
  deregistration_delay = 30

  health_check {
    healthy_threshold   = "5"
    interval            = "30"
    protocol            = "HTTP"
    matcher             = "200"
    timeout             = "5"
    path                = var.health_check_path
    unhealthy_threshold = "2"
  }
    
  lifecycle {
    create_before_destroy = true
    ignore_changes        = [name]
  }

  tags = var.common_tags
}

